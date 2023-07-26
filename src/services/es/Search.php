<?php declare(strict_types=1);

namespace alanrogers\tools\services\es;

use alanrogers\tools\services\ServiceLocator;
use Craft;
use craft\elements\Category;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\models\Section;
use DateTime;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

/**
 * Represents searching a specific `IndexType`
 */
class Search
{
    /**
     * @var ElasticSearch
     */
    protected ElasticSearch $es;

    /**
     * The index for this specific search
     * @var Index
     */
    protected Index $index;

    /**
     * Base constructor.
     * @param ElasticSearch $es
     * @param Index $type
     */
    public function __construct(ElasticSearch $es, Index $type)
    {
        $this->es = $es;
        $this->index = $type;
    }

    /**
     * @return ElasticSearch
     */
    public function getES(): ElasticSearch
    {
        return $this->es;
    }

    /**
     * The index definition for the searched configured for this instance
     * @return Index
     */
    public function getIndex(): Index
    {
        return $this->index;
    }

    protected function fullTextSearchParams(int $size=10, int $from=0) : array
    {
        return [
            'index' => $this->index->indexName(),
            'explain' => Craft::$app->getConfig()->getGeneral()->devMode,
            'body' => [
                'from' => $from,
                'size' => $size,
                '_source' => [
                    'includes' => $this->index->includes_fields,
                    'excludes' => []
                ],
                'query' => [] // <- populated by static::fullTextQuery() - see Base::fullTextSearch()
            ]
        ];
    }

    /**
     * @param string $query
     * @param int $size
     * @param int $from
     * @return SearchResponse
     */
    public function fullTextSearch(string $query, int $size=10, int $from=0) : SearchResponse
    {
        $params = $this->fullTextSearchParams($size, $from);
        try {
            $params['body']['query'] = $this->fullTextQuery($query);
            $response = $this->es->getClient()->search($params);
            return SearchResponse::build($response);
        } catch (ClientResponseException|ServerResponseException|ESException $e) {
            ServiceLocator::getInstance()->error->reportBackendException($e);
            $error = $e->getMessage();
        }
        return SearchResponse::build(null, $error);
    }

    /**
     * A mechanism concrete classes can use to allow / disallow entries from the index
     * @param Entry $entry
     * @return bool
     */
    public function isAllowedInIndex(Entry $entry) : bool
    {
        return true;
    }

    /**
     * @param Entry $entry
     * @return array
     * @throws ESException
     */
    public function transformEntryData(Entry $entry) : array
    {
        $data = [];

        $process_field = static function(string $name, mixed $value, Entry $entry, array &$data) use (&$process_field)
        {
            if ($value instanceof DateTime) {
                $value = $value->format('c');
            } elseif (is_object($value) && method_exists( $value, 'getParsedContent')) { // another way of doing "$value instanceof FieldData" without requiring the redactor plugin in this repo
                $value = strip_tags($value->getParsedContent());
            } elseif ($value instanceof Category) {
                $value = [
                    'slug' => $value->slug,
                    'level' => $value->level
                ];
            } elseif ($value instanceof ElementCollection) {
                throw new ESException(
                    sprintf(
                        'Cannot process `ElementCollection`s directly for field "%s" - use a `field_transformer` in config instead.',
                        $name
                    )
                );
            } elseif ($value instanceof Section) {
                $value = $value->handle;
            } elseif (is_iterable($value)) {
                foreach ($value as $idx => $item) {
                    $value[$idx] = $process_field($name, $item, $entry, $data);
                }
            } elseif (is_string($value)) {
                if (ctype_digit($value)) {
                    $value = (int) $value;
                } else {
                    $value = trim($value);
                }
            } elseif (!$value && !is_numeric($value)) {
                $value = null;
            }
            return $value;
        };

        $fields = [
            ...Config::getInstance()->getGlobalFieldMapping()[$this->index->type->value] ?? [],
            ...$this->index->field_mapping
        ];

        // go through the field transformers in order first as some relay on previous things set
        foreach ($this->index->field_transformers as $field_name => $transformer) {
            $field_value = $entry->$field_name ?? null;
            $data[$field_name] = $this->index->field_transformers[$field_name]($field_name, $field_value, $entry, $data);
            // don't need to go through the field again below:
            unset($fields[$field_name]);
        }

        // go through the other fields
        foreach (array_keys($fields) as $field_name) {
            $field_value = $entry->$field_name ?? null;
            $data[$field_name] = $process_field($field_name, $field_value, $entry, $data);
        }

        return $data;
    }



    /**
     * Gets the matched fields from `$this->getMatchFields()` and applies the boosting from `$this->getFieldBoosts()`
     * and returns the resultant fields array.
     * @return string[]
     */
    public function getMatchFieldsWithBoosts() : array
    {
        $fields = $this->index->getMatchFields();
        $boosts = $this->index->getFieldBoosts();

        if ($boosts) {
            foreach ($fields as $idx => $field) {
                if (isset($boosts[$field])) {
                    $fields[$idx] .= $boosts[$field];
                }
            }
        }

        return $fields;
    }

    /**
     * The root query portion of the full text search for this search.
     * This method must be overridden in extended classes
     * @param string $query The search text
     * @return array
     * @throws ESException
     */
    protected function fullTextQuery(string $query) : array
    {
        throw new ESException('Search classes must implement their own `fullTextQuery()` method.');
    }
}
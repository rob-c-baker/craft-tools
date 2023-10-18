<?php

namespace alanrogers\tools\services\es;

use alanrogers\tools\services\ServiceLocator;

class Index
{
    private const REQUIRED_PROPERTIES = [
        'type',
        'search_class',
        'name'
    ];

    /**
     * The type of index
     * @var IndexType
     */
    public IndexType $type;

    /**
     * The fully qualified name of the class that will be returned from `$this->getSearch()`
     * @var string
     */
    protected string $search_class;

    private ?Search $search_instance = null;

    /**
     * The name of the index, without the site-specific prefix applied
     * @var string
     */
    public string $name;

    /**
     * The label for the search - used wherever a human-friendly name is needed
     * @var string
     */
    public string $label;

    /**
     * The section id for section based indexes, otherwise `0`.
     * @var int
     */
    public int $section_id = 0;

    /**
     * A field mapping suitable for creating ES indexes
     * @var array
     */
    public array $field_mapping = [];

    /**
     * An array of string field names that are matched against.
     * @var string[]
     */
    public array $match_fields = [];

    /**
     * An array of fields to be included in the ES response
     * @var array
     */
    public array $includes_fields = [];

    /**
     * An array of field boosts for this index like:
     * [ 'field_name_1' => '^1', 'field_name_2' => '^7' ]
     * Note this is merged with `Config::getInstance()->getGlobalFieldBoosts()` so `null` values here remove them
     * from the global field boosts.
     * @var string[]
     */
    public array $field_boosts = [];

    /**
     * callbacks used for more complex modification of field data before it gets populated in ES
     * @var array<string, callable>
     */
    public array $field_transformers = [];

    /**
     * A Craft element query ready eager loading map for entries in a section index
     * @var array
     */
    public array $eager_loading = [];

    /**
     * Whether this index should be kept automatically up-to-date. Handled by Craft element events by default.
     * @var bool
     */
    public bool $auto_index = false;

    /**
     * @param string|IndexType $type The `IndexType` ENUM itself or one of the string values of it
     * @param array{
     *         name: string,
     *         label: string,
     *         section_id: int|null,
     *         search_class: string,
     *         match_fields: string[],
     *         field_mapping:  array{ type: string, analyzer: string, ignore_malformed?: boolean, properties: array }[],
     *         includes_fields: string[],
     *         field_boosts: string[],
     *         field_transformers: array<string, callable>,
     *         eager_loading: array[],
     *         auto_index: bool
     *     } $properties
     * @throws ESException
     */
    public function __construct(string|IndexType $type, array $properties)
    {
        $this->type = ($type instanceof IndexType) ? $type : IndexType::from($type);

        // check for existence and throw exceptions if necessary
        foreach (self::REQUIRED_PROPERTIES as $property) {
            if (empty($properties[$property])) {
                throw new ESException(sprintf('Required property "%s" missing for type "%s"', $property, $type->value));
            }
        }

        $this->search_class = $properties['search_class'];
        $this->name = $properties['name'];
        $this->section_id = $properties['section_id'] ?? 0;
        $this->label = $properties['label'];
        $this->match_fields = $properties['match_fields'];
        $this->field_mapping = $properties['field_mapping'];
        $this->includes_fields = $properties['includes_fields'];
        $this->field_boosts = $properties['field_boosts'] ?? [];
        $this->field_transformers = $properties['field_transformers'] ?? [];
        $this->eager_loading = $properties['eager_loading'] ?? [];
        $this->auto_index = $properties['auto_index'] ?? false;
    }

    /**
     * Gets the `Search` class object associated with this index
     * @return Search|null
     * @throws ESException
     */
    public function getSearch() : ?Search
    {
        if ($this->search_instance === null) {
            if (class_exists($this->search_class)) {
                $this->search_instance = new $this->search_class(ServiceLocator::getInstance()->elastic_search, $this);
            } else {
                throw new ESException(sprintf('Search class "%s" does not exist.', $this->search_class));
            }
        }
        return $this->search_instance;
    }

    /**
     * The index name optionally including any prefix set. ES index names must be lowercase, this converts into a lowercase string.
     * This is necessary because `$this->name` may be a section name which can contain uppercase letters.
     * @param bool $add_prefix
     * @return string
     */
    public function indexName(bool $add_prefix=true) : string
    {
        $name = $this->name;
        if ($this->type === IndexType::ALL) {
            // When this is an "all" indexes search, index name should be a comma separated list of all
            // section type index names:
            $names = Config::getInstance()->getAllSectionIndexNames();
            $name = implode(',', $names);
        }
        return self::normaliseIndexName($name, $add_prefix);
    }

    /**
     * Produces an ES compatible index name from the string input.
     * Optionally adds an index prefix from the config for this site.
     * (ES needs a lower-case, slug-like string).
     * @param string $index_identifier
     * @param bool $add_prefix Default: true
     * @return string
     */
    public static function normaliseIndexName(string $index_identifier, bool $add_prefix=true) : string
    {
        if ($add_prefix) {
            $prefix = Config::getInstance()->getIndexPrefix() ?? '';
        } else {
            $prefix = '';
        }
        if ($prefix && str_starts_with($index_identifier, $prefix)) {
            // prefix already present
            return $index_identifier;
        }
        return $prefix . strtolower(
            preg_replace( // convert to a slug-like format
                '/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/',
                '-',
                $index_identifier
            )
        );
    }

    /**
     * Gets field mapping suitable for creating ES indexes
     * @return array
     */
    public function fieldMapping() : array
    {
        return array_filter([
            ...(Config::getInstance()->getGlobalFieldMapping()[$this->type->value] ?? []),
            ...$this->field_mapping
        ]);
    }

    /**
     * @return string[]
     */
    public function getMatchFields() : array
    {
        if ($this->type === IndexType::ALL) {
            $indexes = Config::getInstance()->getIndexes();
            $fields = [];
            foreach ($indexes as $index) {
                if ($index->type === IndexType::SECTION) {
                    $fields = [ ...$fields, ...$index->getMatchFields() ];
                }
            }
            return $fields;
        }
        return $this->match_fields;
    }

    /**
     * Gets a map of field names to boost amounts applied to the array that comes back from `$this->getMatchFields()`.
     * Designed to be overridden in concrete classes.
     * Example: of returned array:
     * [ 'field_name_1' => '^1', 'field_name_2' => '^7' ]
     * @return array{string, array{string}}
     */
    public function getFieldBoosts() : array
    {
        return array_filter([ ...Config::getInstance()->getGlobalFieldBoosts(), ...$this->field_boosts ]);
    }

    /**
     * Returns eager load array for Craft entry query
     * @param array $options
     * @return array
     */
    public function eagerLoads(array $options=[]) : array
    {
        $eager_map = [];
        if ($this->type === IndexType::SECTION) {
            $eager_map = $this->eager_loading['loads'] ?? [];
            if (is_callable($this->eager_loading['modifier'] ?? null)) {
                $eager_map = $this->eager_loading['modifier']($eager_map, $options);
            }
        }
        return $eager_map;
    }
}
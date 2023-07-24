<?php declare(strict_types=1);

namespace alanrogers\tools\services\es;

use alanrogers\tools\services\ServiceLocator;
use craft\log\MonologTarget;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Psr\Log\LogLevel;
use RuntimeException;
use yii\base\Component;

/**
 * Class ElasticSearch
 * @package modules\ar\services\es
 */
class ElasticSearch extends Component
{
    /**
     * @var Client|null
     */
    private static ?Client $_client = null;

    /**
     * @return Client
     * @throws ESException
     */
    public function getClient() : Client
    {
        if (self::$_client === null) {

            /** @var array{ protocol: string, host: string, port: string, username: string|null, password: string|null } $connection */
            $connection = Config::getInstance()->getConnection();

            try {
                $builder = ClientBuilder::create()
                    ->setElasticMetaHeader(false) // don't want / need Meta headers sent to our instance
                    ->setHosts([$connection['protocol'] . '://' . $connection['host'] . ':' . $connection['port']]);

                if ($connection['username'] !== null && $connection['password'] !== null) {
                    $builder->setBasicAuthentication($connection['username'], $connection['password']);
                }

                if (isset($_SERVER['ES_LOGGING_ENABLED']) && $_SERVER['ES_LOGGING_ENABLED']) {
                    $logger = new MonologTarget([
                        'name' => 'elastic-search',
                        'logContext' => false,
                        'level' => LogLevel::WARNING
                    ]);
                    $logger->init();
                    $builder->setLogger($logger->getLogger());
                }

                self::$_client = $builder->build();

            } catch (AuthenticationException $e) {
                throw new ESException('Could not authenticate with ES instance.', (int) $e->getCode(), $e);
            }
        }
        return self::$_client;
    }

    /**
     * Gets an instance of a service class for section based searching.
     * Used from templates or via the ServiceLocator
     * @param string $section_handle
     * @return Search|null
     */
    public function getSearch(string $section_handle) : ?Search
    {
        try {
            return SearchFactory::getSearch($section_handle);
        } catch (ESException $e) {
            ServiceLocator::getInstance()->error->reportBackendException($e);
        }
        return null;
    }

    /**
     * Used to get details of last query, primarily for debugging
     * @throws ESException
     */
    public function getLastQuery() : string
    {
        return $this->getClient()->getTransport()->getLastRequest()->getBody()->getContents();
    }

    /**
     * Create a new index, with the field mapping (schema)
     * @param string $index
     * @param array $mapping
     * @param array|null $settings defaults to settings in `searches\Base::settings()`
     * @return bool
     */
    public function createIndex(string $index, array $mapping, ?array $settings=null): bool
    {
        try {
            $response = $this->getClient()->indices()->create([
                'index' => $index,
                'body' => [
                    'mappings' => [
                        'properties' => $mapping
                    ],
                    'settings' => $settings ?? Config::getInstance()->getGlobalIndexSettings()
                ]
            ]);
        } catch (ClientResponseException|MissingParameterException|ServerResponseException|ESException $e) {
            ServiceLocator::getInstance()->error->reportBackendException($e, true);
            return false;
        }

        return $response->asBool();
    }

    /**
     * Updates the mapping on an existing index
     * @param string $index
     * @param array $mapping
     * @param array|null $settings defaults to settings in `searches\Base::settings()`
     * @return bool
     */
    public function updateIndexMapping(string $index, array $mapping, ?array $settings=null) : bool
    {
        try {
            // see https://www.elastic.co/guide/en/elasticsearch/reference/7.17/indices-update-settings.html
            // cannot update analysers on "open" indexes - they need to be closed, updated and then opened again.
            $indices = $this->getClient()->indices();
            $indices->close([ 'index' => $index ]);
            $response = $this->getClient()->indices()->putMapping([
                'index' => $index,
                'body' => [
                    'properties' => $mapping,
                    'settings' => $settings ?? Config::getInstance()->getGlobalIndexSettings()
                ]
            ]);
        } catch (ClientResponseException|MissingParameterException|ServerResponseException|ESException $e) {
            ServiceLocator::getInstance()->error->reportBackendException($e, true);
            return false;
        }

        return $response->asBool();
    }

    /**
     * Determines if specified index exists.
     * @param string $index
     * @return bool
     */
    public function indexExists(string $index) : bool
    {
        try {
            $response = $this->getClient()->indices()->exists([
                'index' => $index
            ]);
        } catch (ClientResponseException|MissingParameterException|ServerResponseException|ESException $e) {
            throw new RuntimeException('Could not establish if index exists.', (int) $e->getCode(), $e);
        }

        return $response->asBool();
    }

    /**
     * Delete an index completely
     * @param string $index
     * @return bool
     */
    public function deleteIndex(string $index) : bool
    {
        try {
            $response = $this->getClient()->indices()->delete([
                'index' => $index
            ]);
        } catch (ClientResponseException $e) {
            if ($e->getCode() === 404) {
                // when the index is already deleted
                return true;
            }
            ServiceLocator::getInstance()->error->reportBackendException($e, true);
            return false;
        } catch (MissingParameterException|ServerResponseException|ESException $e) {
            ServiceLocator::getInstance()->error->reportBackendException($e, true);
            return false;
        }

        return $response->asBool();
    }

    /**
     * Add some data to the specified index, overwriting any existing document.
     * @param string $index
     * @param int $id
     * @param array $data
     * @param array $override_params (Optional) Any additional $params to supply to ES call
     * @return bool
     */
    public function addToIndex(string $index, int $id, array $data, array $override_params=[]) : bool
    {
        $params = [
            'index' => $index,
            'id' => (string) $id,
            'body' => $data
        ];

        if ($override_params) {
            $params = [ ...$params, ...$override_params ];
        }

        try {
            $response = $this->getClient()->index($params);
        } catch (ClientResponseException|MissingParameterException|ServerResponseException|ESException $e) {
            ServiceLocator::getInstance()->error->reportBackendException($e, true);
            return false;
        }

        return $response->asBool();
    }

    /**
     * Determines if the passed in id exists within passed in index
     * @param string $index
     * @param int $id
     * @param array $override_params (Optional) Any additional $params to supply to ES call
     * @return bool
     */
    public function existsInIndex(string $index, int $id, array $override_params=[]) : bool
    {
        $params = [
            'index' => $index,
            'id' => (string) $id
        ];

        if ($override_params) {
            $params = [ ...$params, ...$override_params ];
        }

        try {
            $response = $this->getClient()->exists($params);
        } catch (ClientResponseException|MissingParameterException|ServerResponseException|ESException $e) {
            ServiceLocator::getInstance()->error->reportBackendException($e, true);
            return false;
        }

        return $response->asBool();
    }

    /**
     * Update data for the data represented by id within the passed in index.
     * @param string $index
     * @param int $id
     * @param array $data
     * @param array $override_params (Optional) Any additional $params to supply to ES call
     * @return bool
     */
    public function updateInIndex(string $index, int $id, array $data, array $override_params=[]) : bool
    {
        $params = [
            'index' => $index,
            'id' => (string) $id,
            'body' => [
                'doc' => $data
            ]
        ];

        if ($override_params) {
            $params = [ ...$params, ...$override_params ];
        }

        try {
            $response = $this->getClient()->update($params);
        } catch (ClientResponseException|MissingParameterException|ServerResponseException|ESException $e) {
            ServiceLocator::getInstance()->error->reportBackendException($e, true);
            return false;
        }

        return $response->asBool();
    }

    /**
     * @param string $index
     * @param int $id
     * @param array $override_params (Optional) Any additional $params to supply to ES call
     * @return bool
     */
    public function deleteFromIndex(string $index, int $id, array $override_params=[]) : bool
    {
        $params = [
            'index' => strtolower($index),
            'id' => (string) $id
        ];

        if ($override_params) {
            $params = [ ...$params, ...$override_params ];
        }

        try {
            $response = $this->getClient()->delete($params);
        } catch (ClientResponseException|MissingParameterException|ServerResponseException|ESException $e) {
            ServiceLocator::getInstance()->error->reportBackendException($e, true);
            return false;
        }

        return $response->asBool();
    }

    /**
     * @param string $index
     * @param string $field
     * @param string $query
     * @param int $size (Default: 10)
     * @param int $from (Default: 0)
     * @param string[] $select Fields to return
     * @return SearchResponse
     */
    public function searchInField(string $index, string $field, string $query, int $size=10, int $from=0, array $select=[]) : SearchResponse
    {
        if ($size > 10000) {
            $size = 10000;
        }

        return $this->searchInFields($index, [ 'match' => [ $field => $query ] ], $size, $from, $select);
    }

    /**
     * @param string $index
     * @param array $query_criteria
     * @param int $size (Default: 10)
     * @param int $from (Default: 0)
     * @param string[] $select Fields to return
     * @param array $additional_root_params
     * @return SearchResponse
     */
    public function searchInFields(string $index, array $query_criteria, int $size=10, int $from=0, array $select=[], array $additional_root_params=[]) : SearchResponse
    {
        if ($size > 10000) {
            $size = 10000;
        }

        $params = [
            'index' => $index,
            'body' => [
                'from' => $from,
                'size' => $size,
                'query' => $query_criteria
            ]
        ];

        if ($select) {
            $params['body']['_source'] = $select;
        }

        if ($additional_root_params) {
            foreach ($additional_root_params as $key => $root_param) {
                $params['body'][$key] = $root_param;
            }
        }

        try {
            $response = $this->getClient()->search($params);
        } catch (ClientResponseException|ServerResponseException|ESException $e) {
            ServiceLocator::getInstance()->error->reportBackendException($e, true);
            return SearchResponse::build(null, $e->getMessage());
        }

        return SearchResponse::build($response);
    }

    /**
     * @param string $index
     * @param string $query
     * @param string[] $fields The fields to search in (@see Helper::getCampsiteMatchFields())
     * @param string[] $includes The fields to return from elastic search
     * @param int $size (Default: 10)
     * @param int $from (Default: 0)
     * @return SearchResponse
     */
    public function fullTextSearch(string $index, array $fields, array $includes, string $query, int $size=10, int $from=0) : SearchResponse
    {
        $params = [
            'index' => $index,
            'body' => [
                'from' => $from,
                'size' => $size,
                '_source' => [
                    'includes' => $includes, // fields to include in _source response
                    'excludes' => []
                ],
                'query' => [
                    'bool' => [
                        'must' => [
                            'multi_match' => [
                                'query' => $query,
                                'fields' => $fields,
                            ]
                        ],
                        'should' => [
                            'match' => [
                                'has_main_image' => [
                                    'query' => true,
                                    'boost' => 20
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = $this->getClient()->search($params);
        } catch (ClientResponseException|ServerResponseException|ESException $e) {
            ServiceLocator::getInstance()->error->reportBackendException($e, true);
            return SearchResponse::build(null, $e->getMessage());
        }

        return SearchResponse::build($response);
    }
}
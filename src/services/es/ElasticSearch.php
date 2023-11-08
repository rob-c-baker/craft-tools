<?php declare(strict_types=1);

namespace alanrogers\tools\services\es;

use alanrogers\tools\traits\ErrorManagementTrait;
use craft\log\MonologTarget;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Psr\Log\LogLevel;
use RuntimeException;
use Throwable;
use yii\base\Component;

/**
 * Class ElasticSearch
 * @package modules\ar\services\es
 * @property-read Client $client
 * @property-read string $lastQuery
 */
class ElasticSearch extends Component
{
    use ErrorManagementTrait;

    /**
     * Whether to throw exceptions
     * @var bool
     */
    private bool $throw_exceptions = false;

    /**
     * @var Client|null
     */
    private static ?Client $_client = null;

    /**
     * @param bool $state
     * @return $this
     */
    public function setThrowExceptions(bool $state) : ElasticSearch
    {
        $this->throw_exceptions = $state;
        return $this;
    }

    /**
     * Note: this will always throw an exception if there is an error as nothing can be done without a connection to
     * the ES instance.
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
                $msg = 'Could not authenticate with ES instance.';
                $this->addError($msg, 'getClient');
                $this->addError($e->getMessage(), 'getClient');
                throw new ESException($msg, (int) $e->getCode(), $e);
            }
        }
        return self::$_client;
    }

    /**
     * Gets an instance of a service class for section based searching.
     * Used from templates or via the `ServiceLocator`.
     * @param string $index_name
     * @return Search|null
     */
    public function getSearch(string $index_name) : ?Search
    {
        try {
            return SearchFactory::getSearch($index_name);
        } catch (ESException $e) {
            $this->addError($e->getMessage(), 'getSearch');
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
     * @throws ESException When `$this->throw_exceptions` is `true`
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
            $this->addError($e->getMessage(), 'createIndex');
            if ($this->throw_exceptions) {
                throw new ESException($e->getMessage(), (int) $e->getCode(), $e);
            }
            return false;
        }

        return $response->asBool();
    }

    /**
     * Updates the mapping on an existing index
     * @param string $index
     * @param array $mapping
     * @return bool
     * @throws ESException When `$this->throw_exceptions` is `true`
     */
    public function updateIndexMapping(string $index, array $mapping) : bool
    {
        $index_open = true;
        try {
            // see https://www.elastic.co/guide/en/elasticsearch/reference/7.17/indices-update-settings.html
            // cannot update analysers on "open" indexes - they need to be closed, updated and then opened again.
            $indices = $this->getClient()->indices();
            $indices->close([ 'index' => $index ]);
            $index_open = false;
            $response = $this->getClient()->indices()->putMapping([
                'index' => $index,
                'body' => [
                    'properties' => $mapping,
                ]
            ]);
            $indices->open([ 'index' => $index ]);
            $index_open = true;
        } catch (ClientResponseException|MissingParameterException|ServerResponseException|ESException $e) {
            $this->addError($e->getMessage(), 'updateIndexMapping');
            if ($this->throw_exceptions) {
                throw new ESException($e->getMessage(), (int) $e->getCode(), $e);
            }
            return false;
        } finally {
            if (!$index_open) {
                try {
                    $this->getClient()->indices()->open([ 'index' => $index ]);
                } catch (Throwable $e) {
                    throw new RuntimeException('ES: Could not re-open index on failed mapping update.', (int) $e->getCode(), $e);
                }
            }
        }

        return $response->asBool();
    }

    /**
     * @throws ESException When `$this->throw_exceptions` is `true`
     */
    public function updateIndexSettings(string $index, array $settings) : bool
    {
        $index_open = true;
        try {
            // see https://www.elastic.co/guide/en/elasticsearch/reference/7.17/indices-update-settings.html
            // cannot update analysers on "open" indexes - they need to be closed, updated and then opened again.
            $indices = $this->getClient()->indices();
            $indices->close([ 'index' => $index ]);
            $index_open = false;
            $response = $this->getClient()->indices()->putSettings([
                'index' => $index,
                'body' => [
                    'settings' => $settings,
                ]
            ]);
            $indices->open([ 'index' => $index ]);
            $index_open = true;
        } catch (ClientResponseException|MissingParameterException|ServerResponseException|ESException $e) {
            $this->addError($e->getMessage(), 'updateIndexSettings');
            if ($this->throw_exceptions) {
                throw new ESException($e->getMessage(), (int) $e->getCode(), $e);
            }
            return false;
        } finally {
            if (!$index_open) {
                try {
                    $this->getClient()->indices()->open([ 'index' => $index ]);
                } catch (Throwable $e) {
                    throw new RuntimeException('ES: Could not re-open index on failed settings update.', (int) $e->getCode(), $e);
                }
            }
        }

        return $response->asBool();
    }

    /**
     * Determines if specified index exists.
     * Note: always throws exceptions when something goes wrong
     * @param string $index
     * @return bool
     * @throws ESException
     */
    public function indexExists(string $index) : bool
    {
        try {
            $response = $this->getClient()->indices()->exists([
                'index' => $index
            ]);
        } catch (ClientResponseException|MissingParameterException|ServerResponseException|ESException $e) {
            $msg = 'Could not establish if index exists.';
            $this->addError($msg, 'indexExists');
            $this->addError($e->getMessage(), 'indexExists');
            throw new ESException($msg, (int) $e->getCode(), $e);
        }

        return $response->asBool();
    }

    /**
     * Delete an index completely
     * @param string $index
     * @return bool
     * @throws ESException When `$this->throw_exceptions` is `true`
     */
    public function deleteIndex(string $index) : bool
    {
        try {
            $response = $this->getClient()->indices()->delete([
                'index' => $index
            ]);
        } catch (ClientResponseException $e) {
            $this->addError($e->getMessage(), 'deleteIndex');
            if ($e->getCode() === 404) {
                // when the index is already deleted
                return true;
            }
            if ($this->throw_exceptions) {
                throw new ESException($e->getMessage(), (int) $e->getCode(), $e);
            }
            return false;
        } catch (MissingParameterException|ServerResponseException|ESException $e) {
            $this->addError($e->getMessage(), 'deleteIndex');
            if ($this->throw_exceptions) {
                throw new ESException($e->getMessage(), (int) $e->getCode(), $e);
            }
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
     * @throws ESException When `$this->throw_exceptions` is `true`
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
            $this->addError($e->getMessage(), 'addToIndex');
            if ($this->throw_exceptions) {
                throw new ESException($e->getMessage(), (int) $e->getCode(), $e);
            }
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
     * @throws ESException When `$this->throw_exceptions` is `true`
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
            $this->addError($e->getMessage(), 'existsInIndex');
            if ($this->throw_exceptions) {
                throw new ESException($e->getMessage(), (int) $e->getCode(), $e);
            }
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
     * @throws ESException When `$this->throw_exceptions` is `true`
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
            $this->addError($e->getMessage(), 'updateInIndex');
            if ($this->throw_exceptions) {
                throw new ESException($e->getMessage(), (int) $e->getCode(), $e);
            }
            return false;
        }

        return $response->asBool();
    }

    /**
     * @param string $index
     * @param int $id
     * @param array $override_params (Optional) Any additional $params to supply to ES call
     * @return bool
     * @throws ESException When `$this->throw_exceptions` is `true`
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
            $this->addError($e->getMessage(), 'deleteFromIndex');
            if ($this->throw_exceptions) {
                throw new ESException($e->getMessage(), (int) $e->getCode(), $e);
            }
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
     * @throws ESException When `$this->throw_exceptions` is `true`
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
     * @throws ESException When `$this->throw_exceptions` is `true`
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
            $this->addError($e->getMessage(), 'searchInFields');
            if ($this->throw_exceptions) {
                throw new ESException($e->getMessage(), (int) $e->getCode(), $e);
            } else {
                return SearchResponse::build(null, $e->getMessage());
            }
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
     * @throws ESException When `$this->throw_exceptions` is `true`
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
            $this->addError($e->getMessage(), 'fullTextSearch');
            if ($this->throw_exceptions) {
                throw new ESException($e->getMessage(), (int) $e->getCode(), $e);
            } else {
                return SearchResponse::build(null, $e->getMessage());
            }
        }

        return SearchResponse::build($response);
    }
}
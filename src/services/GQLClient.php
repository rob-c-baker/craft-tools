<?php

namespace alanrogers\tools\services;

use Craft;
use craft\helpers\Json;
use RuntimeException;
use yii\base\Component;

/**
 * Class GQLClient
 * @package modules\ar\services
 */
class GQLClient extends Component
{
    public const DEFAULT_TTL = 3600;

    /**
     * @var int
     */
    private int $cache_ttl = self::DEFAULT_TTL;

    /**
     * @var string
     */
    private string $endpoint = '';

    /**
     * @var string
     */
    private string $query = '';

    /**
     * @var string
     */
    private string $token = '';

    /**
     * @var bool
     */
    private bool $cache_enabled = true;

    /**
     * @var bool
     */
    private bool $executed = false;

    /**
     * @var null|bool|array
     */
    private $response = null;

    /**
     * @param bool $state
     * @param int|null $ttl
     * @return $this
     */
    public function setCacheEnabled(bool $state, ?int $ttl=null) : self
    {
        $this->cache_enabled = $state;
        if ($ttl) {
            $this->cache_ttl = $ttl;
        }
        return $this;
    }

    /**
     * @param string $endpoint
     * @return $this
     */
    public function setEndPoint(string $endpoint) : self
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * @param string $query
     * @return $this
     */
    public function setQuery(string $query) : self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @param string $token
     * @return $this
     */
    public function setToken(string $token) : self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        if ($this->cache_enabled) {
            $cache_key = self::getCacheKey(
                $this->endpoint,
                $this->query,
                $this->token
            );
            if (Craft::$app->getCache()->exists($cache_key)) {
                $this->response = Craft::$app->getCache()->get($cache_key);
                return $this->response;
            }
        }

        $headers = [
            'Content-Type: application/graphql',
            'User-Agent: AR GraphQL client'
        ];

        if ($this->token) {
            $headers[] = "Authorization: bearer $this->token";
        }

        $stream_options = [
            'http' => [
                'method' => 'POST',
                'header' => $headers,
                'content' => $this->query,
            ]
        ];

        $this->response = @file_get_contents($this->endpoint, false, stream_context_create($stream_options));

        if ($this->response === false) {
            $error = error_get_last();
            throw new RuntimeException($error['message'], $error['type']);
        }

        $this->response = Json::decode($this->response);
        $this->executed = true;

        if (isset($this->response['errors'])) {
            $msg = sprintf(
                '[%s] %s',
                $this->response['errors'][0]['category'] ?? 'unknown',
                $this->response['errors'][0]['message']
            );
            throw new RuntimeException($msg);
        }

        if ($this->cache_enabled && $this->response && empty($this->response['errors'])) {
            /** @noinspection PhpUndefinedVariableInspection */
            Craft::$app->getCache()->set($cache_key, $this->response, $this->cache_ttl);
        }

        return $this->response;
    }

    /**
     * @return array|null
     */
    public function getResponse(): ?array
    {
        if (!$this->executed) {
            $this->execute();
        }
        return $this->response;
    }

    /**
     * @param string $endpoint
     * @param string $query
     * @param string $token
     * @return string
     */
    private static function getCacheKey(string $endpoint, string $query, string $token='') : string
    {
        return 'gql-' . md5(implode('|', [
            $endpoint,
            $query,
            $token
        ]));
    }

    /**
     * So we can easily call this from Twig via ServiceManager
     * @param string $endpoint
     * @return self
     */
    public function instance(string $endpoint='') : self
    {
        return (new self())->setEndPoint($endpoint);
    }
}
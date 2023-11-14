<?php declare(strict_types=1);

namespace alanrogers\tools\services\es;

use alanrogers\tools\services\ServiceLocator;
use alanrogers\tools\services\Config as ConfigStore;
use Exception;

class Config
{
    private static ?Config $instance = null;

    private ConfigStore $config;

    /**
     * An array of all indexes available
     * @var Index[]
     */
    private ?array $indexes = null;

    /**
     * Optionally pass in an instance to read the stored config from for easier testing
     * @param ConfigStore|null $config_store
     * @return Config
     */
    public static function getInstance(ConfigStore $config_store=null): Config
    {
        if (self::$instance === null) {
            self::$instance = new Config($config_store);
        }
        return self::$instance;
    }

    /**
     * Resets to fresh state - for testing.
     * @return void
     */
    public static function resetInstance() : void
    {
        self::$instance = null;
    }

    /**
     * Public for testing
     * @param ConfigStore|null $config_store
     */
    public function __construct(ConfigStore $config_store=null)
    {
        if ($config_store) {
            $this->config = $config_store;
        } else {
            $this->config = new ConfigStore([
                'default_config_name' => 'elastic-search'
            ]);
        }
    }

    /**
     * Determines if ES is enabled. Only enabled if the config file exists and config option is set to enabled
     * @return bool
     */
    public function isEnabled() : bool
    {
        return $this->config->configExists() && $this->config->getItem('enabled');
    }
    
    /**
     * @return array{ protocol: string, host: string, port: string, username: string|null, password: string|null }
     */
    public function getConnection(): array
    {
        return $this->config->getItem('connection');
    }

    /**
     * Gets any defined index prefix - note: converts to lowercase because indexes in ES have to be lowercase.
     * @return string|null
     */
    public function getIndexPrefix(): ?string
    {
       return strtolower($this->config->getItem('index_prefix')) ?? null;
    }

    public function getGlobalFieldMapping(): array
    {
        return $this->config->getItem('global_field_mapping') ?? [];
    }

    /**
     * The global settings for all indexes - used when the index mapping is defined or updated
     * @return array|null
     */
    public function getGlobalIndexSettings() : ?array
    {
        return $this->config->getItem('global_index_settings') ?? null;
    }

    /**
     * Gets a map of field names to boost amounts applied to the array that comes back from `$this->getMatchFields()`.
     * Designed to be overridden in concrete classes.
     * Example: of returned array:
     * [ 'field_name_1' => '^1', 'field_name_2' => '^7' ]
     * @return array
     */
    public function getGlobalFieldBoosts() : array
    {
        return $this->config->getItem('global_field_boosts') ?? [];
    }

    /**
     * An array of all indexes defined for this site
     * @return Index[]
     */
    public function getIndexes(): array
    {
        if ($this->indexes === null) {
            $this->loadIndexes();
        }
        return $this->indexes;
    }

    private function loadIndexes() : void
    {
        foreach ($this->config->getItem('indexes') as $index_data) {
            try {
                $this->indexes[] = new Index($index_data['type'], $index_data, $this);
            } catch (Exception $e) {
                ServiceLocator::getInstance()->error->reportBackendException($e, true);
            }
        }
    }

    /**
     * @param string $name
     * @param bool $normalise_name If true (default), will convert name to an ES compatible name
     * @return Index|null
     */
    public function getIndexByName(string $name, bool $normalise_name=true) : ?Index
    {
        if ($normalise_name) {
            $name = $this->normaliseIndexName($name, false);
        }
        foreach ($this->getIndexes() as $index) {
            if ($index->name === $name) {
                return $index;
            }
        }
        return null;
    }

    /**
     * @return string[]
     */
    public function getAllSectionIndexNames() : array
    {
        $names = [];
        foreach ($this->getIndexes() as $index) {
            if ($index->type === IndexType::SECTION) {
                $names[] = $index->indexName();
            }
        }
        return $names;
    }

    /**
     * Produces an ES compatible index name from the string input.
     * Optionally adds an index prefix from the config for this site.
     * (ES needs a lower-case, slug-like string).
     * @param string $index_identifier
     * @param bool $add_prefix Default: true (if false and the prefix is found then it will be removed from the name)
     * @return string
     */
    public function normaliseIndexName(string $index_identifier, bool $add_prefix=true) : string
    {
        $prefix = $this->getIndexPrefix() ?? '';

        if ($prefix && str_starts_with($index_identifier, $prefix)) {
            // prefix already present so remove it (before possibly adding it again below)
            $index_identifier = substr($index_identifier, strlen($prefix));
        }

        return mb_strtolower(preg_replace( // convert to a slug-like format
            '/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/',
            '-',
            ($add_prefix ? $prefix : '') . $index_identifier
        ));
    }
}
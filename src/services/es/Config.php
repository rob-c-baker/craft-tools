<?php declare(strict_types=1);

namespace alanrogers\tools\services\es;
use alanrogers\tools\services\ServiceLocator;
use Exception;

class Config
{
    private static ?Config $instance = null;

    private \alanrogers\tools\services\Config $config;

    /**
     * An array of all indexes available
     * @var Index[]
     */
    private ?array $indexes = null;

    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->config = new \alanrogers\tools\services\Config([
            'default_config_name' => 'elastic-search'
        ]);
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

    public function getIndexPrefix(): ?string
    {
       return $this->config->getItem('index_prefix') ?? null;
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
                $this->indexes[] = new Index($index_data['type'], $index_data);
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
            $name = Index::normaliseIndexName($name, false);
        }
        foreach ($this->getIndexes() as $index) {
            if ($index->name === $name) {
                return $index;
            }
        }
        return null;
    }

    /**
     * @param string $handle
     * @param bool $normalise_name If true (default), will convert name to an ES compatible name
     * @return Index|null
     */
    public function getIndexBySectionHandle(string $handle, bool $normalise_name=true) : ?Index
    {
        if ($normalise_name) {
            $name = Index::normaliseIndexName($handle, false);
        } else {
            $name = $handle;
        }
        foreach ($this->getIndexes() as $index) {
            if ($index->type === IndexType::SECTION && $index->name === $name) {
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
}
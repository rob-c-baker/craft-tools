<?php declare(strict_types=1);

namespace alanrogers\tools\services;

use alanrogers\tools\exceptions\ServiceLocatorException;
use alanrogers\tools\helpers\BaseHelper;
use alanrogers\tools\helpers\HelperInterface;
use alanrogers\tools\services\errors\ErrorHandler;
use alanrogers\tools\services\es\ElasticSearch;
use yii\base\InvalidConfigException;
use yii\di\ServiceLocator as YiiServiceLocator;

/**
 * @property GQLClient $gql_client
 * @property Error $error
 * @property ErrorHandler $error_handler
 * @property Config $config
 * @property AlanRogersCache $cache
 * @property-read YiiServiceLocator $helpers
 * @property ElasticSearch $elastic_search
 */
class ServiceLocator extends YiiServiceLocator
{
    private static ?ServiceLocator $_instance = null;
    private ?YiiServiceLocator $_helpers = null;

    /**
     * Array of namespaces to look for helpers - uses the first match it finds.
     * @var string[]
     */
    protected static array $_helper_namespaces = [
        'alanrogers\\tools\\helpers\\'
    ];

    protected function __construct()
    {
        parent::__construct();
        $this->_helpers = new YiiServiceLocator();
    }

    public function init() : void
    {
        parent::init();

        // default components
        $this->setComponents([
            'cache' => AlanRogersCache::class,
            'gql_client' => GQLClient::class,
            'error' => Error::class,
            'error_handler' => ErrorHandler::class,
            'elastic_search' => ElasticSearch::class,
            'config' => Config::class
        ]);
    }

    /**
     * Adds a new namespace to look for helpers.
     * NOTE: adds to the beginning of the array so if there are name collisions, the most recently set namespace wins.
     * @param string $ns
     * @return void
     */
    public static function registerHelperNamespace(string $ns) : void
    {
        array_unshift(self::$_helper_namespaces, $ns);
    }

    /**
     * Gets the singleton instance of the service locator
     * @return ServiceLocator
     */
    public static function getInstance() : ServiceLocator
    {
        if (ServiceLocator::$_instance === null) {
            ServiceLocator::$_instance = new ServiceLocator();
            $components = ServiceLocator::$_instance->config->getItem('components');
            if ($components) {
                ServiceLocator::$_instance->setComponents($components);
            }
        }
        return ServiceLocator::$_instance;
    }

    public function getHelpers() : YiiServiceLocator
    {
        return $this->_helpers;
    }

    /**
     * @throws ServiceLocatorException
     * @return BaseHelper|HelperInterface
     */
    public function helper(string $name) : ?object
    {
        $helper_sl = ServiceLocator::getInstance()->getHelpers();
        if ($helper_sl->has($name)) {
            try {
                return $helper_sl->get($name);
            } catch (InvalidConfigException $e) {
                throw new ServiceLocatorException($e->getMessage(), $e->getCode(), $e);
            }
        }

        // Nothing saved, lets try to load the helper...

        foreach (self::$_helper_namespaces as $ns) {
            $class_name = $ns . $name;
            if (class_exists($class_name)) {
                try {
                    $helper_sl->set($name, $class_name);
                    return $helper_sl->get($name);
                } catch (InvalidConfigException $e) {
                    throw new ServiceLocatorException($e->getMessage(), $e->getCode(), $e);
                }
            }
        }

        return null;
    }
}
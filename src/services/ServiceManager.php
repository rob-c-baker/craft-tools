<?php
declare(strict_types=1);

namespace alanrogers\tools\services;

use alanrogers\tools\helpers\BaseHelper;
use alanrogers\tools\helpers\HelperInterface;
use alanrogers\tools\services\errors\ErrorHandler;
use alanrogers\tools\services\errors\reporters\Sentry;
use RuntimeException;
use yii\base\InvalidArgumentException;

/**
 * Class ServiceManager
 * @property GQLClient $gql_client
 * @property Error $error
 * @property ErrorHandler $error_handler
 * @property Config $config
 */
class ServiceManager
{
    protected static array $_service_classes = [
        'gql_client' => GQLClient::class,
        'error' => Error::class,
        'error_handler' => ErrorHandler::class,
        'config' => Config::class
    ];

    /**
     * Array of namespaces to look for helpers - uses the first match it finds.
     * @var string[]
     */
    protected static array $_helper_namespaces = [
        'alanrogers\\tools\\helpers\\'
    ];

    protected static array $_helpers = [];

    protected static array $_instances = [];

    final protected function __construct() {}

    public static function getInstance() : static
    {
        if (!isset(self::$_instances[static::class])) {
            self::$_instances[static::class] = new static();
            self::$_instances[static::class]->init();
        }
        return self::$_instances[static::class];
    }

    public function init() : void
    {
        // Special case for error_handler: need to register Sentry as early as possible
        $this->error_handler->setSuppressedExceptionCodes([ 404, 410 ]);
        $this->error_handler->getReporter(Sentry::class)->initialise();
    }

    /**
     * Allows fluent access to the services this class manager.
     * Sets a property so subsequent calls should not go through this method (the property will then exist!).
     * @param string $name
     * @return mixed|void
     */
    public function __get(string $name)
    {
        if (isset(self::$_service_classes[$name]) && self::$_service_classes[$name]) {
            $this->$name = new self::$_service_classes[$name]();
            return $this->$name;
        }
        throw new InvalidArgumentException(sprintf('ServiceManager: Cannot get - No such AR service: "%.50s"', $name));
    }

    /**
     * @param string $name
     * @param $value
     */
    public function __set(string $name, $value)
    {
        if (isset(self::$_service_classes[$name])) {
            $this->$name = $value;
            return;
        }
        throw new InvalidArgumentException(sprintf('ServiceManager: Cannot set - No such AR service: "%.50s"', $name));
    }


    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name)
    {
        return isset(self::$_service_classes[$name]);
    }

    /**
     * Make the service manager aware of 1 or more service classes.
     * @template T
     * @param array<string, class-string<T>> $service_classes (key => class name)
     * @return void
     */
    public static function registerServiceClasses(array $service_classes) : void
    {
        self::$_service_classes = array_replace(self::$_service_classes, $service_classes);
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
     * Method generally called by twig to get an instance of a helper
     * @param string $name
     * @return BaseHelper|HelperInterface
     */
    public function helper(string $name) : BaseHelper|HelperInterface
    {
        $class_name = '';
        foreach (self::$_helper_namespaces as $ns) {
            $class_name = $ns . $name;
            if (isset(self::$_helpers[$class_name])) {
                return self::$_helpers[$class_name];
            }
            if (class_exists($class_name)) {
                self::$_helpers[$class_name] = new $class_name();
                break;
            }
        }
        if (!isset(self::$_helpers[$class_name])) {
            throw new RuntimeException('No helper class found with the name: ' . $class_name);
        }
        return self::$_helpers[$class_name];
    }
}
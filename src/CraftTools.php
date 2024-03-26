<?php
declare(strict_types=1);

namespace alanrogers\tools;

use alanrogers\tools\controllers\SitemapsController;
use alanrogers\tools\fields\FieldRegister;
use alanrogers\tools\rules\UserRules;
use alanrogers\tools\services\es\Events;
use alanrogers\tools\services\ServiceLocator;
use alanrogers\tools\services\sitemap\SitemapConfig;
use alanrogers\tools\twig\Extensions;
use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\console\Application as Console;
use craft\controllers\UsersController;
use craft\elements\User;
use craft\events\DefineRulesEvent;
use craft\events\RegisterCacheOptionsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\i18n\PhpMessageSource;
use craft\utilities\ClearCaches;
use craft\web\UrlManager;
use craft\web\View;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\ActionEvent;
use yii\base\Controller;
use yii\base\Event;
use yii\base\Exception;
use yii\web\ForbiddenHttpException;

/**
 * @property ServiceLocator $ar
 */
class CraftTools extends Plugin
{
    const ID = '_ar-tools';

    public string $schemaVersion = '1.0.0';

    /**
     * Static property that is an instance of this module class so that it can be accessed via CraftTools::$instance
     * @var CraftTools
     */
    public static CraftTools $instance;

    public $controllerMap = [
        'sitemaps' => SitemapsController::class
    ];

    /**
     * An array of field handles that must NOT be edited by the owning user.
     * @var array
     */
    public static array $disallowed_custom_user_fields = [];

    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    /**
     * @throws SyntaxError
     * @throws Exception
     * @throws RuntimeError
     * @throws LoaderError
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('_craft-test-plugin/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    public function init(): void
    {
        parent::init();

        // Alias for this module
        Craft::setAlias('@modules/alanrogers', $this->getBasePath());

        // Define a custom alias named after the namespace
        Craft::setAlias('@' . $this->id, __DIR__);

        // Alias for AR Module Twig templates
        Craft::setAlias('@' . $this->id . '-templates', __DIR__ . '/templates');

        // controller namespace
        if (Craft::$app instanceof Console) {
            $this->controllerNamespace = 'alanrogers\\tools\\console\\controllers';
        } else {
            $this->controllerNamespace = 'alanrogers\\tools\\controllers';
        }

        // Our custom fields
        FieldRegister::registerFields();

        $this->registerTranslationCategory();

        self::enforceFieldPermissions();

        // Front-end routes
        self::registerFrontEndRoutes();

        // AR cache
        self::arCacheEvents();

        // Elasticsearch events
        Events::registerEvents();

        Craft::$app->onInit(function() {

            Extensions::register();

            $this->setComponents([
                'ar' => ServiceLocator::getInstance()
            ]);

            self::registerUserRules();

            Craft::$app->getView()->hook('seo-fields', function(array &$context) {
                return ServiceLocator::getInstance()->seo->renderHead($context);
            });
        });

        // Need front-end templates (cp template root gets registered in parent class)
        Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $e) {
            if (is_dir($base_dir = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates')) {
                $e->roots[$this->id] = $base_dir;
            }
        });
    }

    private static function registerFrontEndRoutes() : void
    {
        if (SitemapConfig::isEnabled()) {
            Event::on(
                UrlManager::class,
                UrlManager::EVENT_REGISTER_SITE_URL_RULES,
                function(RegisterUrlRulesEvent $event) {
                    $event->rules['sitemap.xml'] = self::ID . '/sitemaps/list';
                    $event->rules['sitemaps/<identifier:{slug}>.xml'] = self::ID . '/sitemaps/xml';
                }
            );
        }
    }

    private static function arCacheEvents() : void
    {
        // Register cache purge checkbox
        Event::on(
            ClearCaches::class,
            ClearCaches::EVENT_REGISTER_CACHE_OPTIONS,
            function (RegisterCacheOptionsEvent $event) {
                $event->options[] = [
                    'key'    => 'ar-cache-purge-all',
                    'label'  => 'Alan Rogers Redis Cache',
                    'action' => function () {
                        ServiceLocator::getInstance()->cache->flush();
                    },
                ];
            }
        );
    }

    private function registerTranslationCategory() : void
    {
        Craft::$app->getI18n()->translations[$this->id] = [
            'class' => PhpMessageSource::class,
            'sourceLanguage' => 'en',
            'basePath' => __DIR__ . '/translations',
            'allowOverrides' => true,
        ];
    }

    private static function registerUserRules() : void
    {
        // Enhance username and password security...
        Event::on(
            User::class,
            Model::EVENT_DEFINE_RULES,
            static function(DefineRulesEvent $event) {
                foreach(UserRules::define() as $rule) {
                    $event->rules[] = $rule;
                }
            });
    }

    private static function enforceFieldPermissions() : void
    {
        Event::on(
            UsersController::class,
            Controller::EVENT_BEFORE_ACTION,
            function(ActionEvent $event) {

                // @todo make this into an event
                if ($event->action->id === 'save-user' && Craft::$app->request->isSiteRequest) {

                    // check whether each of the fields submitted in the posted field's parameter (which can be renamed
                    // using the fieldsLocation param in Craft 3.6.13 and above) is disallowed
                    $request = Craft::$app->getRequest();
                    $fieldsLocation = $request->getBodyParam('fieldsLocation', 'fields');
                    $fields = $request->getBodyParam($fieldsLocation, []);

                    foreach ($fields as $key => $value) {
                        // Throw an exception if the field is disallowed.
                        if (in_array($key, self::$disallowed_custom_user_fields, true)) {
                            throw new ForbiddenHttpException('One or more disallowed fields were submitted.');
                        }
                    }
                }
            }
        );
    }
}
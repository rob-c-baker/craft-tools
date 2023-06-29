<?php
declare(strict_types=1);

namespace alanrogers\tools;

use alanrogers\tools\fields\FieldRegister;
use alanrogers\tools\rules\UserRules;
use alanrogers\tools\services\ServiceManager;
use alanrogers\tools\twig\Extensions;
use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\controllers\UsersController;
use craft\elements\User;
use craft\events\DefineRulesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\i18n\PhpMessageSource;
use craft\web\View;
use yii\base\ActionEvent;
use yii\base\Controller;
use yii\base\Event;
use yii\web\ForbiddenHttpException;

/**
 * @property ServiceManager $ar
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

        Craft::$app->onInit(function() {

            $this->setComponents([
                'ar' => ServiceManager::getInstance()
            ]);

            Extensions::register();

            // Our custom fields
            FieldRegister::registerFields();

            $this->registerTranslationCategory();
            self::registerUserRules();
            self::enforceFieldPermissions();

            // Need front-end templates (cp template root gets registered in parent class)
            Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $e) {
                if (is_dir($base_dir = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates')) {
                    $e->roots[$this->id] = $base_dir;
                }
            });
        });
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
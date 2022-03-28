<?php

namespace alanrogers\tools;

use alanrogers\tools\fields\FieldRegister;
use alanrogers\tools\rules\UserRules;
use alanrogers\tools\services\ServiceManager;
use alanrogers\tools\twig\Extensions;
use Craft;
use craft\base\Model;
use craft\controllers\UsersController;
use craft\elements\User;
use craft\events\DefineRulesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use yii\base\ActionEvent;
use yii\base\Controller;
use yii\base\Event;
use yii\base\Module;
use yii\web\ForbiddenHttpException;

/**
 * @property ServiceManager $ar
 */
class CraftTools extends Module
{
    /**
     * Static property that is an instance of this module class so that it can be accessed via
     * CraftTools::$instance
     * @var CraftTools
     */
    public static CraftTools $instance;

    /**
     * @var ServiceManager|null
     */
    private static ?ServiceManager $service_manager = null;

    /**
     * An array of field handles that must NOT be edited by the owning user.
     * @var array
     */
    public static array $disallowed_custom_user_fields = [];

    public function init()
    {
        parent::init();

        self::$instance = $this;

        $this->setComponents([
            'ar' => $this->getServiceManager(),
        ]);

        $base_path = $this->getBasePath();

        // Alias for this module
        Craft::setAlias('@modules/alanrogers', $base_path);

        // Register Twig stuff
        Extensions::register();

        // Our custom fields
        FieldRegister::registerFields();

        self::registerTemplateRoot($base_path);
        self::registerUserRules();
        self::enforceFieldPermissions();

        // Set this as the global instance of this module class
        static::setInstance($this);
    }

    /**
     * @return ServiceManager
     */
    public function getServiceManager() : ServiceManager
    {
        if (self::$service_manager === null) {
            self::$service_manager = new ServiceManager();
        }
        return self::$service_manager;
    }

    private static function registerTemplateRoot(string $base_path) : void
    {
        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) use ($base_path) {
                $event->roots['alanrogers'] = $base_path . '/templates';
            }
        );
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
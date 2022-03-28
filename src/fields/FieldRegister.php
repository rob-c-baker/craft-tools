<?php

namespace alanrogers\tools\fields;

use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\services\Fields;
use craft\web\View;
use yii\base\Event;

class FieldRegister
{
    public static function registerFields() : void
    {
        self::registerSitesField();
    }

    private static function registerSitesField()
    {
        // Sites field based on https://github.com/tonioseiler/propagated-sites-field-plugin
        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['_sites-field'] = __DIR__ . '/templates/sites-field';
            }
        );

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = SitesField::class;
            }
        );
    }
}
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
        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['_sites-field'] = __DIR__ . '/templates/sites-field';
                $event->roots['template-select'] = __DIR__ . '/templates/template-select-field';
            }
        );

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = SitesField::class;
                $event->types[] = TemplateSelectField::class;
            }
        );
    }
}
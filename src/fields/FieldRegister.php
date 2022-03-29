<?php

namespace alanrogers\tools\fields;

use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\services\Fields;
use craft\web\View;
use yii\base\Event;

class FieldRegister
{
    public static function registerFields(string $base_dir) : void
    {
        self::registerSitesField($base_dir);
    }

    private static function registerSitesField(string $base_dir)
    {
        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) use ($base_dir) { // @todo maybe use alias here?
                $event->roots['_sites-field'] = $base_dir . '/templates/sites-field';
                $event->roots['template-select'] = $base_dir . '/templates/template-select-field';
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
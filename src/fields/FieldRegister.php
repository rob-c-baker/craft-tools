<?php
declare(strict_types=1);

namespace alanrogers\tools\fields;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use yii\base\Event;

class FieldRegister
{
    public static function registerFields() : void
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = SitesField::class;
                $event->types[] = TemplateSelectField::class;
                $event->types[] = SectionField::class;
                $event->types[] = CategoryGroupsField::class;
            }
        );
    }
}
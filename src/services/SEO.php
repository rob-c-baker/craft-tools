<?php declare(strict_types=1);

namespace alanrogers\tools\services;

use alanrogers\tools\fields\SEOField;
use alanrogers\tools\models\SEOFieldModel;
use craft\base\ElementInterface;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;

class SEO
{
    public function renderHead(array &$twig_context): string
    {
        // @todo
    }

    public function getConfig(?ElementInterface $element = null): array
    {
        // @todo implementing the way settings from global > section > element override each other
    }

    private function getFieldModelFromTwigContext(array &$twig_context, ?string $field_handle=null): ?SEOFieldModel
    {
        $element_classes = [ // @todo in config
            Entry::class,
            Category::class,
            Tag::class
        ];

        /** @var SEOFieldModel|null $field */
        $model = null;
        /** @var ElementInterface $element */
        $element = null;

        foreach ($element_classes as $fq_class) {
            $class_parts = explode('\\', $fq_class);
            $element_name = end($class_parts);
            if (isset($twig_context[$element_name])) {
                $model = $twig_context[$element_name][$field_handle] ?? new SEOFieldModel();
                $element = $twig_context[$element_name];
                break;
            }
        }

        if ($model) {
            $model->element = $element;
        }

        return $model;
    }

    public function getSEOFieldFromElement(ElementInterface $element): ?SEOField
    {
        // @todo
    }
}
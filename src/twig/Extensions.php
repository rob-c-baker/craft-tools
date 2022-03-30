<?php

namespace alanrogers\tools\twig;

use Craft;
use Exception;
use alanrogers\tools\twig\extensions\DeepMerge;
use alanrogers\tools\twig\extensions\Inline;
use Twig\TwigFilter;

class Extensions
{
    public static function register() : void
    {
        // Add our Twig filters
        Craft::$app->view->twig->addFilter(new TwigFilter('is_array', function($value) {
            return is_array($value);
        }));
        Craft::$app->view->twig->addFilter(new TwigFilter('array_values', function($value) {
            return array_values($value);
        }));
        Craft::$app->view->twig->addFilter(new TwigFilter('intval', function($value) {
            return intval($value);
        }));
        Craft::$app->view->twig->addFilter(new TwigFilter('push', function($value, $push1, $push2=null) {
            if ($push2) {
                // If there is a second param then  $push1 is the key and push2 is the value
                $value[$push1] = $push2;
            } else {
                // If no second param, then $push1 is the value
                $value[] = $push1;
            }
            return $value;
        }));

        try {
            Craft::$app->view->registerTwigExtension(new extensions\MaterialDesignIcons());
        } catch (Exception $e) {
            Craft::error($e->getMessage(), 'MaterialDesignIcons');
        }

        // Deep merge ability
        Craft::$app->view->registerTwigExtension(new DeepMerge());

        // Our inline function
        Craft::$app->view->registerTwigExtension(new Inline());

        // Our globals
        Craft::$app->view->registerTwigExtension(new TwigGlobals());
    }
}
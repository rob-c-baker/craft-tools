<?php

namespace alanrogers\tools\twig;

use alanrogers\tools\twig\extensions\Dumper;
use alanrogers\tools\twig\extensions\Image64;
use alanrogers\tools\twig\extensions\UnsetVariable;
use Craft;
use Exception;
use alanrogers\tools\twig\extensions\DeepMerge;
use alanrogers\tools\twig\extensions\Inline;
use Twig\TwigFilter;

class Extensions
{
    public static function register() : void
    {
        $view = Craft::$app->getView();
        $twig = $view->getTwig();

        // Add our Twig filters
        $twig->addFilter(new TwigFilter('is_array', function($value) {
            return is_array($value);
        }));
        $twig->addFilter(new TwigFilter('array_values', function($value) {
            return array_values($value);
        }));
        $twig->addFilter(new TwigFilter('intval', function($value) {
            return intval($value);
        }));
        $twig->addFilter(new TwigFilter('push', function($value, $push1, $push2=null) {
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
            $view->registerTwigExtension(new extensions\MaterialDesignIcons());
        } catch (Exception $e) {
            Craft::error($e->getMessage(), 'MaterialDesignIcons');
        }

        // Deep merge ability
        $view->registerTwigExtension(new DeepMerge());

        // Our inline function
        $view->registerTwigExtension(new Inline());

        // Base 64 image encoding
        $view->registerTwigExtension(new Image64());

        // Our globals
        $view->registerTwigExtension(new TwigGlobals());

        // ability to unset()
        $view->registerTwigExtension(new UnsetVariable());

        // ability to unset()
        $view->registerTwigExtension(new Dumper());
    }
}
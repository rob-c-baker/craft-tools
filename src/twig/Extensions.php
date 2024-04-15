<?php declare(strict_types=1);

namespace alanrogers\tools\twig;

use alanrogers\tools\twig\extensions\Dumper;
use alanrogers\tools\twig\extensions\Image64;
use alanrogers\tools\twig\extensions\TwigURLHelper;
use alanrogers\tools\twig\extensions\VariableTools;
use alanrogers\tools\twig\perversion\Extension;
use Craft;
use alanrogers\tools\twig\extensions\DeepMerge;
use alanrogers\tools\twig\extensions\Inline;
use LogicException;
use Twig\TwigFilter;

class Extensions
{
    public static function register() : void
    {
        $view = Craft::$app->getView();
        $twig = $view->getTwig();

        try {
            // Add our Twig filters
            $twig->addFilter(new TwigFilter('is_array', function($value) {
                return is_array($value);
            }));
        } catch (LogicException $e) {
            // might get here if for some reason they are already registered.
        }
        
        try {
            $twig->addFilter(new TwigFilter('array_values', function($value) {
                return array_values($value);
            }));
        } catch (LogicException $e) {
            // might get here if for some reason they are already registered.
        }

        try {
            $twig->addFilter(new TwigFilter('intval', function($value) {
                return intval($value);
            }));
        } catch (LogicException $e) {
            // might get here if for some reason they are already registered.
        }

        try {
            $twig->addFilter(new TwigFilter('str_hash', function($value, string $algorithm, array $options=[]) {
                return hash($algorithm, $value, false, $options);
            }));
        } catch (LogicException $e) {
            // might get here if for some reason they are already registered.
        }

        try {
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
        } catch (LogicException $e) {
            // might get here if for some reason they are already registered.
        }

        // Twig perversion plugin: https://github.com/marionnewlevant/craft-twig_perversion
        $view->registerTwigExtension(new Extension());

        // Material Design icons
        $view->registerTwigExtension(new extensions\MaterialDesignIcons());

        // Deep merge ability
        $view->registerTwigExtension(new DeepMerge());

        // Our inline function
        $view->registerTwigExtension(new Inline());

        // Base 64 image encoding
        $view->registerTwigExtension(new Image64());

        // ability to unset(), set variables and other variable related stuff
        $view->registerTwigExtension(new VariableTools());

        // ability to unset()
        $view->registerTwigExtension(new Dumper());

        // URL Helper
        $view->registerTwigExtension(new TwigURLHelper());
    }
}
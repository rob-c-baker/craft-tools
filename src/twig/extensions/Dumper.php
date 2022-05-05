<?php

namespace alanrogers\tools\twig\extensions;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Template;
use Twig\TwigFunction;
use alanrogers\tools\services\Dumper as DumperService;

class Dumper extends AbstractExtension
{
    public function getFunctions() : array
    {
        // dump is safe if var_dump is overridden by xdebug
        $isDumpOutputHtmlSafe = extension_loaded('xdebug')
            // false means that it was not set (and the default is on) or it explicitly enabled
            && (false === ini_get('xdebug.overload_var_dump') || ini_get('xdebug.overload_var_dump'))
            // false means that it was not set (and the default is on) or it explicitly enabled
            // xdebug.overload_var_dump produces HTML only when html_errors is also enabled
            && (false === ini_get('html_errors') || ini_get('html_errors'))
            || 'cli' === PHP_SAPI;

        $options = [
            'is_safe' => $isDumpOutputHtmlSafe ? ['html'] : [],
            'needs_context' => true,
            'needs_environment' => true,
            'debug' => true,
        ];

        return [
            new TwigFunction('d', [Dumper::class, 'd'], $options),
            new TwigFunction('dd', [Dumper::class, 'dd'], $options),
            new TwigFunction('dump', [Dumper::class, 'dump'], [
                'is_safe'           => $isDumpOutputHtmlSafe ? ['html'] : [],
                'needs_context'     => true,
                'needs_environment' => true,
            ]),
        ];
    }

    /**
     * Shorthand for `dump()`
     * @param Environment $env
     * @param array $context
     * @param mixed ...$items
     */
    public static function d(Environment $env, array $context, ...$items)
    {
        if (!$env->isDebug()) {
            return;
        }

        if (!$items) {
            // No parameters passed in - dump entire twig context instead
            $items = self::collectContext($context);
        }

        DumperService::instance()->d(...$items);
    }

    /**
     * Dump and die!
     * @param Environment $env
     * @param array $context
     * @param mixed ...$items
     */
    public static function dd(Environment $env, array $context, ...$items)
    {
        if (!$env->isDebug()) {
            return;
        }

        if (!$items) {
            // No parameters passed in - dump entire twig context instead
            $items = self::collectContext($context);
        }

        DumperService::instance()->dd(...$items);
    }

    /**
     * Override dump version of Symfony's VarDumper component
     *
     * @param Environment $env
     * @param array $context
     * @param mixed ...$items
     */
    public static function dump(Environment $env, array $context, ...$items) : void
    {
        if (!$env->isDebug()) {
            return;
        }

        if (!$items) {
            // No parameters passed in - dump entire twig context instead
            $items = self::collectContext($context);
        }

        DumperService::instance()->dump(...$items);
    }

    private static function collectContext(array $context) : array
    {
        $items = [];
        foreach ($context as $key => $value) {
            if (!$value instanceof Template) {
                $items[$key] = $value;
            }
        }
        return $items;
    }
}
<?php

namespace alanrogers\tools\services;

use Symfony\Component\VarDumper\VarDumper;

class Dumper
{
    private static ?Dumper $instance = null;

    public static function instance() : Dumper
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param mixed ...$items
     */
    public function d(...$items)
    {
        foreach ($items as $item) {
            echo dump($item);
        }
        echo '<style>pre.sf-dump { z-index: 0; !important} </style>';
    }

    /**
     * @param mixed ...$items
     */
    public function dd(...$items)
    {
        echo '<style>pre.sf-dump { z-index: 0; !important} </style>';
        dd(...$items);
    }

    public function dump(...$items) : void
    {
        ob_start();
        foreach ($items as $item) {
            VarDumper::dump($item);
        }
        echo ob_get_clean();
    }
}
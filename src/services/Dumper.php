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
        $i   = 0;
        $len = count($items);
        foreach ($items as $item) {
            if ($i == $len - 1) {
                echo dd($item);
            } else {
                echo dump($item);
            }
            $i++;
        }
        echo '<style>pre.sf-dump { z-index: 0; !important} </style>';
    }

    public function dump(...$items) : void
    {
        ob_start();
        VarDumper::dump($items);
        echo ob_get_clean();
    }
}
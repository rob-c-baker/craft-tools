<?php

namespace alanrogers\tools;

use alanrogers\tools\twig\Extensions;
use yii\base\Module;

class CraftTools extends Module
{
    /**
     * Static property that is an instance of this module class so that it can be accessed via
     * CraftTools::$instance
     * @var CraftTools
     */
    public static CraftTools $instance;

    public function init()
    {
        parent::init();

        self::$instance = $this;

        // Register Twig stuff
        Extensions::register();

        // Set this as the global instance of this module class
        static::setInstance($this);
    }
}
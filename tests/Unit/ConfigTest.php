<?php

namespace Tests\Unit;

use alanrogers\tools\services\Config;
use Tests\Support\UnitTester;

class ConfigTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    protected function _before()
    {
    }

    public function testGetBasicValue()
    {
        $config = new Config([
            'base_path' => __DIR__ . '/../Support/Data/config/',
            'default_config_name' => 'test-config'
        ]);
        $this->assertEquals('hello', $config->getItem('item_2'));
    }

    public function testGetItems()
    {
        $config = new Config([
            'base_path' => __DIR__ . '/../Support/Data/config/',
            'default_config_name' => 'test-config'
        ]);
        $this->assertEquals([ 'hello', 0 ], $config->getItems([ 'item_2', 'item_1' ]));
    }

    public function testGetAllItems()
    {
        $config = new Config([
            'base_path' => __DIR__ . '/../Support/Data/config/',
            'default_config_name' => 'test-config'
        ]);
        $expected = require __DIR__ . '/../Support/Data/config/test-config.php';
        $this->assertEquals($expected, $config->getAllItems());
    }
}

<?php

namespace Tests\Unit;

use alanrogers\tools\services\es\Config;
use Tests\Support\UnitTester;

class ElasticSearchTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    protected function _before()
    {
    }

    public function testIndexNameNormalisation() : void
    {
        // Setup config...
        $config_store = new \alanrogers\tools\services\Config([
            'default_config_name' => 'elastic-search'
        ]);
        $config_store->setAllItems([
            'index_prefix' => 'ar-'
        ]);
        $es_config = new Config($config_store);

        $this->assertEquals(
            'ar-test',
            $es_config->normaliseIndexName('test', true),
            'Check "test" -> "ar-test" while adding prefix.'
        );

        $this->assertEquals(
            'ar-test',
            $es_config->normaliseIndexName('ar-test', true),
            'Check "ar-test" -> "ar-test" while adding prefix.'
        );

        $this->assertEquals(
            'test',
            $es_config->normaliseIndexName('ar-test', false),
            'Check "ar-test" -> "test" while NOT adding prefix.'
        );

        $this->assertEquals(
            'test',
            $es_config->normaliseIndexName('test', false),
            'Check "test" -> "test" while NOT adding prefix.'
        );
    }
}
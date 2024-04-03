<?php

namespace Tests\Unit;

use alanrogers\tools\helpers\Url;
use Tests\Support\UnitTester;

class UrlHelperTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    public function testUrlIsSame()
    {
        $this->assertTrue(
            Url::isURLSame(
                'https://alanrogers.com/part1/part2',
                'https://alanrogers.com/part1/part2'
            ),
            'Checking URLs are the same'
        );

        $this->assertTrue(
            Url::isURLSame(
                'https://alanrogers.com/part1/part2',
                'https://alanrogers.com/part1/part2?query=1#hash',
            ),
            'Checking URLs are the same - with QS / hash'
        );

        $this->assertFalse(
            Url::isURLSame(
                'https://alanrOgers.com/parT1/part2',
                'https://alanroGers.com/paAt1/parT2?query=1#hash',
                true
            ),
            'Checking URLs are the same - case sensitive'
        );
    }
}
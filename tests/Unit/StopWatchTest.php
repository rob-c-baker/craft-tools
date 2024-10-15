<?php declare(strict_types=1);

namespace Unit;

use alanrogers\tools\services\StopWatch;
use Tests\Support\UnitTester;

class StopWatchTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    protected function _before()
    {
    }

    public function testHumanReadable(): void
    {
        $this->assertEquals('27h 46m 44s 925ms', StopWatch::humanReadable(100004925400));
        $this->assertEquals('16m 40s 45ms', StopWatch::humanReadable(1000045000));
        $this->assertEquals('01s 0ms', StopWatch::humanReadable(1000000));
        $this->assertEquals('0ms', StopWatch::humanReadable(0));
    }
}
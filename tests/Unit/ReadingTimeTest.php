<?php

namespace Tests\Unit;

use alanrogers\tools\services\ReadingTime;
use Tests\Support\UnitTester;

class ReadingTimeTest extends \Codeception\Test\Unit
{
    const LONG_TEXT = 'This is a simple test that takes a couple of seconds to read. This is a simple test that takes a 
        couple of seconds to read. This is a simple test that takes a couple of seconds to read. This is a simple 
        test that takes a couple of seconds to read. This is a simple test that takes a couple of seconds to read. 
        This is a simple test that takes a couple of seconds to read. This is a simple test that takes a couple of 
        seconds to read. This is a simple test that takes a couple of seconds to read. This is a simple test that takes a 
        couple of seconds to read. This is a simple test that takes a couple of seconds to read. This is a simple 
        test that takes a couple of seconds to read. This is a simple test that takes a couple of seconds to read. 
        This is a simple test that takes a couple of seconds to read. This is a simple test that takes a couple of 
        seconds to read. This is a simple test that takes a couple of seconds to read. This is a simple test that takes a 
        couple of seconds to read. This is a simple test that takes a couple of seconds to read. This is a simple 
        test that takes a couple of seconds to read. This is a simple test that takes a couple of seconds to read. 
        This is a simple test that takes a couple of seconds to read. This is a simple test that takes a couple of 
        seconds to read.
        This is a simple test that takes a couple of seconds to read. This is a simple test that takes a 
        couple of seconds to read. This is a simple test that takes a couple of seconds to read. This is a simple 
        test that takes a couple of seconds to read. This is a simple test that takes a couple of seconds to read. 
        This is a simple test that takes a couple of seconds to read. This is a simple test that takes a couple of 
        seconds to read.';

    protected UnitTester $tester;

    protected function _before()
    {
    }

    public function testFormatImplode()
    {
        $reading_time = new ReadingTime(self::LONG_TEXT, [
            'implode' => true,
            'implode_char' => ':',
            'wpm' => 200
        ]);
        $this->assertEquals('1:49', $reading_time->format(), 'Basic format of implode');
    }

    public function testWordsPerMinute()
    {
        $reading_time = new ReadingTime(self::LONG_TEXT, [
            'implode' => true,
            'implode_char' => ':',
            'wpm' => 250
        ]);
        $this->assertEquals('1:27', $reading_time->format(), 'Test format of different word per minute count');
    }

    public function testEmptyText()
    {
        $reading_time = new ReadingTime('');
        $this->assertEquals(0, $reading_time->count(), 'Empty text word count');

        $reading_time = new ReadingTime('', [
            'implode' => true,
            'implode_char' => '@'
        ]);
        $this->assertEquals('0@00', $reading_time->format(), 'Empty text format');
    }

    public function testStripHTML()
    {
        $text = '<span>This is <em>a simple</em> test that tak<strong>es</strong> a couple of seconds to read.</span>';
        $reading_time = new ReadingTime($text, [
            'strip_tags' => true
        ]);
        $this->assertEquals(13, $reading_time->count(), 'Check HTML stripped OK');
    }

    public function testShortFormat()
    {
        $text = 'This is a simple test that takes a couple of seconds to read.';
        $reading_time = new ReadingTime($text, [
            'wpm' => 200
        ]);
        $this->assertEquals('3s', $reading_time->format(), 'Basic check for short sentence');
        $this->assertEquals(13, $reading_time->count(), 'Basic check for word count');
    }

    public function testLongFormat()
    {
        $text = str_repeat(self::LONG_TEXT, 101);
        $reading_time = new ReadingTime($text, [
            'wpm' => 200
        ]);
        $this->assertEquals('3h 3m 49s', $reading_time->format(), 'Basic check for short sentence');
        $this->assertEquals(36764, $reading_time->count(), 'Basic check for word count');
    }
}

<?php

namespace alanrogers\tools\services;

class ReadingTime
{
    private const int SECONDS_PER_MINUTE = 60;
    private const int|float SECONDS_PER_HOUR = 60 * 60;
    private const int|float SECONDS_PER_DAY = 60 * 60 * 24;

    private const array DEFAULT_OPTIONS = [
        'strip_tags' => true,
        'wpm' => 200,
        'format' => '%d %h %m %s',
        'implode_char' => ':',
        'implode' => false
    ];

    /**
     * @var string
     */
    private string $text;

    /**
     * @var array{ strip_tags: boolean, wpm: int, format: string, implode_char: string, implode: boolean }
     */
    private array $options;

    /**
     * The number of words in the text
     * @var int
     */
    private int $word_count = 0;

    /**
     * @var array{ days: int, hours: int, minutes: int, seconds: int }
     */
    private array $duration = [
        'days' => 0,
        'hours' => 0,
        'minutes' => 0,
        'seconds' => 0
    ];

    /**
     * The number of seconds to read the text
     * @var int
     */
    private int $seconds = 0;

    /**
     * @param string $text Text for word count
     * @param array{ strip_tags: boolean, wpm: int, format: string, implode_char: string, implode: boolean } $options
     */
    public function __construct(string $text, array $options=[])
    {
        $this->text = $text;
        $this->options = [ ...self::DEFAULT_OPTIONS, ...$options ];
        $this->calculate();
    }

    private function calculate() : void
    {
        if ($this->options['strip_tags']) {
            $this->text = strip_tags($this->text);
        }
        $this->word_count = str_word_count($this->text);
        $this->seconds = (int) floor($this->word_count / ($this->options['wpm'] / 60));

        $hours_seconds = $this->seconds % self::SECONDS_PER_DAY;
        $minutes_seconds = $hours_seconds % self::SECONDS_PER_HOUR;

        $this->duration = [
            'days' => floor($this->seconds / self::SECONDS_PER_DAY),
            'hours' => floor($hours_seconds / self::SECONDS_PER_HOUR),
            'minutes' => floor($minutes_seconds / self::SECONDS_PER_MINUTE),
            'seconds' => ceil($minutes_seconds % self::SECONDS_PER_MINUTE)
        ];
    }

    /**
     * Returns the number of words in `$this->text`
     * @return int
     */
    public function count() : int
    {
        return $this->word_count;
    }

    /**
     * Gets total time to read in hours based on `$this->options['wpm']`
     * @return int
     */
    public function hours() : int
    {
        return (int) ceil($this->seconds / 60 / 60);
    }

    /**
     * Gets total time to read in minutes based on `$this->options['wpm']`
     * @return int
     */
    public function minutes() : int
    {
        return (int) ceil($this->seconds / 60);
    }

    /**
     * Gets total time to read in seconds based on `$this->options['wpm']`
     * @return int
     */
    public function seconds() : int
    {
        return $this->seconds;
    }

    /**
     * Gets formatted string based on the various formatting options
     * @return string
     */
    public function format() : string
    {
        if ($this->options['implode']) {
            $formatted = implode($this->options['implode_char'], array_filter($this->duration, function($value, $key) {
                $ok = $value > 0;
                if (in_array($key, [ 'hours', 'seconds' ], true) && $ok) {
                    $this->duration[$key] = str_pad((string) $value, 2, '0', STR_PAD_LEFT);
                }
                return $ok;
            }, ARRAY_FILTER_USE_BOTH));
            if (!$formatted) {
                return '0' . $this->options['implode_char'] . '00';
            }
            return $formatted;
        }

        return trim(str_replace([ '%d', '%h', '%m', '%s'], [
            $this->duration['days'] > 0 ?  $this->duration['days']. 'd' : '',
            $this->duration['hours'] > 0 ?  $this->duration['hours'] . 'h' : '',
            $this->duration['minutes'] > 0 ? $this->duration['minutes'] . 'm' : '',
            $this->duration['seconds'] . 's' // always include seconds in case of an empty string passed in
        ], $this->options['format']));
    }
}
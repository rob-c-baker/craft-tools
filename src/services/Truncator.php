<?php

namespace alanrogers\tools\services;

use InvalidArgumentException;

class Truncator
{
    public const array UNIT_CHARACTERS = [ 'c', 'chars', 'characters' ];
    public const array UNIT_WORDS = [ 'w', 'words' ];
    public const array UNIT_PARAGRAPHS = [ 'p', 'paragraphs' ];

    public function __construct(
        private readonly string   $content,
        private readonly int      $limit = 1,
        private readonly string   $unit = 'p',
        private readonly ?string  $append = null,
        private readonly ?array   $allowed_tags = null
    ) {}

    public function truncate(): string
    {
        $result = '';
        $plain = strip_tags($this->content);

        if (in_array($this->unit, self::UNIT_CHARACTERS)) {

            $clean_content = strip_tags($this->content, $this->allowed_tags);

            if (mb_strlen($plain) <= $this->limit) {
                $result = $clean_content;
            } else {
                $result = preg_replace('/\s+?(\S+)?$/u', '', mb_substr($clean_content, 0, $this->limit)) . $this->append;
            }

        } elseif (in_array($this->unit, self::UNIT_WORDS)) {

            $clean_content = strip_tags($this->content, $this->allowed_tags);

            if (str_word_count($plain) <= $this->limit) {
                $result = $clean_content;
            } else {
                $word_count = str_word_count($clean_content);
                if ($word_count > $this->limit) {
                    $words = preg_split('/\s+/u', $clean_content);
                    $clean_content = implode(' ', array_slice($words, 0, $this->limit));
                    $result = $clean_content;
                    if (preg_match("/[0-9.!?,;:]$/u", $clean_content)) {
                        $result = mb_substr($clean_content, 0, -1);
                    }
                    $result .= $this->append;
                }
            }

        } elseif (in_array($this->unit, self::UNIT_PARAGRAPHS)) {

            $clean_content = strip_tags($this->content, [  '<p>', ...$this->allowed_tags ]);
            $paragraphs = array_filter(explode("<p>", str_replace("</p>", "", $clean_content)));
            $paragraphs = array_slice($paragraphs, 0, $this->limit);
            $paragraphs_count = count($paragraphs) - 1;
            foreach ($paragraphs as $key => $paragraph) {
                $result .= "<p>" . $paragraph;
                if ($key < $paragraphs_count) {
                    $result .= "</p>";
                }
            }

            $result .= $this->append . "</p>";

        } else {
            throw new InvalidArgumentException('Invalid unit: ' . $this->unit);
        }

        return $this->finishTags($result);
    }

    private function finishTags(string $content): string
    {
        preg_match_all('#<(?!meta|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $content, $result);
        $opened = $result[1];

        preg_match_all('#</([a-z]+)>#iU', $content, $result);
        $closed = $result[1];

        $opened_count = count($opened);

        if (count($closed) === $opened_count) {
            return $content;
        }

        $opened = array_reverse($opened);

        for ($i=0; $i < $opened_count; $i++) {
            if (!in_array($opened[$i], $closed)) {
                $content .= '</'.$opened[$i].'>';
            } else {
                unset($closed[array_search($opened[$i], $closed)]);
            }
        }

        return $content;
    }
}
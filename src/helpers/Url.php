<?php
declare(strict_types=1);

namespace alanrogers\tools\helpers;

class Url
{
    /**
     * Opposite of parse_url() with one parameter. Re-builds a URL.
     * @param array $parts
     * @return string
     */
    public static function build(array $parts) : string
    {
        $scheme   = isset($parts['scheme']) ? ($parts['scheme'] . '://') : '';

        $host     = $parts['host'] ?? '';
        $port     = isset($parts['port']) ? (':' . $parts['port']) : '';

        $user     = $parts['user'] ?? '';
        $pass     = isset($parts['pass']) ? (':' . $parts['pass'])  : '';
        $pass     = ($user || $pass) ? ($pass . '@') : '';

        $path     = $parts['path'] ?? '';

        $query    = empty($parts['query']) ? '' : ('?' . $parts['query']);

        $fragment = empty($parts['fragment']) ? '' : ('#' . $parts['fragment']);

        return implode('', [ $scheme, $user, $pass, $host, $port, $path, $query, $fragment ]);
    }

    /**
     * Are the 2 passed in URLs the same based on checking everything apart from query string and hash portions.
     * @param string $url_1
     * @param string $url_2
     * @param bool $case_sensitive
     * @return bool
     */
    public static function isURLSame(string $url_1, string $url_2, bool $case_sensitive=false) : bool
    {
        // strip query strings
        $url_1 = strtok($url_1, '?');
        $url_2 = strtok($url_2, '?');

        // strip hashes
        $url_1 = strtok($url_1, '#');
        $url_2 = strtok($url_2, '#');

        if (!$case_sensitive) {
            $url_1 = mb_strtolower($url_1);
            $url_2 = mb_strtolower($url_2);
        }

        return $url_1 === $url_2;
    }
}
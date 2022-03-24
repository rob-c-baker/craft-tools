<?php

namespace alanrogers\tools\services;

use yii\base\Component;

/**
 * Class PwnedPassword
 *
 * Securely checks if a password has been previously compromised.
 *
 * Only ever transmits the first 5 characters of an SHA1() of the password.
 *
 * @see https://www.troyhunt.com/enhancing-pwned-passwords-privacy-with-padding/
 * @package modules\ar\services
 */
class PwnedPassword extends Component
{
    private const API_URL = 'https://api.pwnedpasswords.com/range/%.5s';

    // @todo cache!

    /**
     * Returns zero for not pwned or an integer greater than zero to indicate how many times it has been pwned.
     * Function returns false if there was no response from the API.
     * @param string $password
     * @return int | false
     */
    public static function isPasswordPwned(string $password)
    {
        $long_hash = self::makeHash($password);
        $short_hash = self::makeShortHash($long_hash);

        $response = self::makeRequest($short_hash);

        if ($response) {
            $data = self::processResponse($response);
            return self::getPasswordCount($data, $long_hash);
        }

        return false;
    }

    /**
     * @param array $data
     * @param string $long_hash
     * @return int
     */
    private static function getPasswordCount(array $data, string $long_hash) : int
    {
        return $data[substr($long_hash, 5)] ?? 0;
    }

    /**
     * @param string $short_hash
     * @return false|string
     */
    private static function makeRequest(string $short_hash)
    {
        $context = [
            'http' => [
                'method' => 'GET',
                'header' =>
                    "User-Agent: AlanRogersWebSite v1.0.0\r\n"
                    . "Add-Padding: true\r\n"
                    . "Accept-Encoding: gzip;q=1.0, *;q=0.5"
            ]
        ];

        $url = sprintf(self::API_URL, $short_hash);

        $response = file_get_contents($url, false, stream_context_create($context));

        // Is the string gzipped?
        if (0 === mb_strpos($response, "\x1f" . "\x8b" . "\x08")) {
            $response = gzdecode($response);
        }

        return $response;
    }

    /**
     * @param string $response
     * @return array
     */
    private static function processResponse(string $response) : array
    {
        $data = [];
        $lines = explode("\r\n", $response);
        foreach ($lines as $line) {
            [ $hash, $count ] = explode(':', $line);
            $data[$hash] = (int) $count;
        }
        return $data;
    }

    /**
     * @param string $password
     * @return string
     */
    private static function makeHash(string $password) : string
    {
        return strtoupper(sha1($password));
    }

    /**
     * @param string $long_hash
     * @return string
     */
    private static function makeShortHash(string $long_hash) : string
    {
        return substr($long_hash, 0, 5);
    }
}
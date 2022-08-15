<?php
declare(strict_types=1);

namespace alanrogers\tools\services;

use Craft;
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
    private const CACHE_TTL = 86400;

    private const API_URL = 'https://api.pwnedpasswords.com/range/%.5s';

    /**
     * Returns zero for not pwned or an integer greater than zero to indicate how many times it has been pwned.
     * Function returns false if there was no response from the API.
     * @param string $password
     * @return int | false
     */
    public static function isPasswordPwned(string $password)
    {
        $cache = Craft::$app->getCache();
        $long_hash = self::makeHash($password);
        $short_hash = self::makeShortHash($long_hash);

        $cache_key = 'pwned_password_' . $short_hash;
        if ($cache->exists($cache_key)) {
            $response = $cache->get($cache_key);
        } else {
            $response = self::makeRequest($short_hash);
            $cache->set($cache_key, $response, self::CACHE_TTL);
        }

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
        $url = sprintf(self::API_URL, $short_hash);

        // need to use curl for this (basic `file_get_contents()` approach with an HTTP stream does not work over TLS)
        $c = curl_init();

        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_TIMEOUT, 5);
        curl_setopt($c, CURLOPT_HTTPHEADER, [
            'User-Agent: AlanRogersWebSite v1.0.0',
            'Add-Padding: true'
        ]);

        // The contents of the "Accept-Encoding: " header. This enables decoding of the response. Supported encodings
        // are "identity", "deflate", and "gzip". If an empty string, "", is set, a header containing all supported
        // encoding types is sent.
        curl_setopt($c, CURLOPT_ENCODING, '');

        $response = curl_exec($c);
        $http_code = (int) curl_getinfo($c,CURLINFO_HTTP_CODE);
        curl_close($c);

        if ($http_code !== 200) {
            return '';
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
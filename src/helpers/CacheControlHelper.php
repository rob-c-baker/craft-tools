<?php declare(strict_types=1);

namespace alanrogers\tools\helpers;

use alanrogers\tools\helpers\HelperInterface;
use Craft;
use craft\web\Response;

class CacheControlHelper implements HelperInterface
{
    /**
     * @param int $cache_time
     * @param Response|null $response
     */
    public static function setCacheHeaders(int $cache_time = 31536000, ?Response $response = null) : void
    {
        if (!$response) {
            $response = Craft::$app->getResponse();
        }
        $response->getHeaders()
            ->set('Expires', gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT')
            ->set('Pragma', 'cache')
            ->set('Cache-Control', 'max-age=' . $cache_time);
    }

    /**
     * Set a marker header to tell Varnish to NOT send previously set cache headers to the browser
     * @param Response|null $response
     */
    public static function setClearCacheHeaders(?Response $response = null) : void
    {
        if (!$response) {
            $response = Craft::$app->getResponse();
        }
        $response->getHeaders()
            ->set('X-Remove-Cache-Control', true);
    }


    /**
     * Sets headers telling Varnish / the browser to NOT cache the response
     * @param Response|null $response
     */
    public static function setNoCacheHeaders(?Response $response = null) : void
    {
        if (!$response) {
            $response = Craft::$app->getResponse();
        }
        $response->getHeaders()
            ->set('Expires', '0')
            ->set('Pragma', 'no-cache')
            ->set('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
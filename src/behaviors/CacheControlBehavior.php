<?php
declare(strict_types=1);

namespace alanrogers\tools\behaviors;

use yii\base\Behavior;
use yii\web\Response;

/**
 * Class CacheControlBehavior
 * @package modules\ar\behaviors
 * @property Response $owner
 */
class CacheControlBehavior extends Behavior
{
    protected array $cache_control = [];

    /**
     * Adds a custom Cache-Control directive.
     * @param string $key   The Cache-Control directive name
     * @param mixed  $value The Cache-Control directive value
     */
    public function addCacheControlDirective(string $key, bool|string|int|float $value = true) : void
    {
        $this->cache_control[$key] = $value;
        $this->owner->getHeaders()->set('Cache-Control', $this->getCacheControlHeader());
    }

    /**
     * Removes a Cache-Control directive.
     *
     * @param string $key The Cache-Control directive
     */
    public function removeCacheControlDirective(string $key) : void
    {
        unset($this->cache_control[$key]);
        $this->owner->getHeaders()->set('Cache-Control', $this->getCacheControlHeader());
    }

    /**
     * Returns true if the Cache-Control directive is defined.
     * @param string $key The Cache-Control directive
     * @return bool true if the directive exists, false otherwise
     */
    public function hasCacheControlDirective(string $key) : bool
    {
        return array_key_exists($key, $this->cache_control);
    }

    /**
     * Returns a Cache-Control directive value by name.
     * @param string $key The directive name
     * @return bool|string|int|float|null The directive value if defined, null otherwise
     */
    public function getCacheControlDirective(string $key): bool|string|int|float|null
    {
        return $this->cache_control[$key] ?? null;
    }

    /**
     * Returns the number of seconds after the time specified in the response's Date
     * header when the response should no longer be considered fresh.
     * First, it checks for a s-maxage directive, then a max-age directive, and then it falls
     * back on an expires header. It returns null when no maximum age can be established.
     * @return int|null Number of seconds
     */
    public function getMaxAge() : ?int
    {
        if ($this->hasCacheControlDirective('s-maxage')) {
            return (int) $this->getCacheControlDirective('s-maxage');
        }
        if ($this->hasCacheControlDirective('max-age')) {
            return (int) $this->getCacheControlDirective('max-age');
        }

        return null;
    }

    /**
     * Sets the number of seconds after which the response should no longer be considered fresh.
     * This methods sets the Cache-Control max-age directive.
     * @param int $value Number of seconds
     */
    public function setMaxAge(int $value) : CacheControlBehavior
    {
        $this->addCacheControlDirective('max-age', $value);
        return $this;
    }

    /**
     * Sets the number of seconds after which the response should no longer be considered fresh by shared caches.
     * This method sets the Cache-Control s-maxage directive.
     * @param int $value Number of seconds
     */
    public function setSharedMaxAge(int $value) : CacheControlBehavior
    {
        $this->addCacheControlDirective('public');
        $this->removeCacheControlDirective('private');
        $this->removeCacheControlDirective('no-cache');
        $this->addCacheControlDirective('s-maxage', $value);
        return $this;
    }

    public function getCacheControl() : array
    {
        return $this->cache_control;
    }

    public function setCacheControlDirectiveFromString(string $value = null) : bool
    {
        if ($value === ''|| is_null($value)) {
            return false;
        }
        foreach (explode(', ', $value) as $directive) {
            $parts = explode('=', $directive);
            $this->addCacheControlDirective($parts[0], $parts[1] ?? true);
        }
        return true;
    }

    protected function getCacheControlHeader() : string
    {
        $parts = array();
        ksort($this->cache_control);
        foreach ($this->cache_control as $key => $value) {
            if (true === $value) {
                $parts[] = $key;
            } else {
                if (preg_match('/[^a-zA-Z0-9._-]/', (string) $value)) {
                    $value = '"' . $value . '"';
                }
                $parts[] = "$key=$value";
            }
        }

        return implode(', ', $parts);
    }
}
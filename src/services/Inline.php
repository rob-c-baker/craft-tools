<?php declare(strict_types=1);


namespace alanrogers\tools\services;

use RuntimeException;
use yii\base\Component;

class Inline extends Component
{
    /**
     * @var string
     */
    private static string $base_path = '';

    /**
     * @var bool
     */
    private static bool $base_path_set = false;

    /**
     * @var array
     */
    private static array $loaded = [];

    /**
     * @param string $path
     */
    public static function setBasePath(string $path) : void
    {
        self::$base_path = rtrim($path, '/') . '/';
        self::$base_path_set = true;
    }

    /**
     * @param string $filename
     * @param bool $remote
     * @return string
     */
    public static function inline(string $filename, bool $remote=false) : string
    {
        if (!self::$base_path_set && defined('CRAFT_BASE_PATH')) {
            self::setBasePath(CRAFT_BASE_PATH);
        }

        if ($remote) {
            if (str_starts_with($filename, '//')) {
                $protocol = \Craft::$app->request->isSecureConnection ? 'https://' : 'http://';
                $filename = $protocol . $filename;
            }
            return @file_get_contents($filename);
        }

        $path = self::$base_path . $filename;
        if (!empty(self::$loaded[$path])) {
            return self::$loaded[$path];
        }

        if ($filename === '') {
            throw new RuntimeException('Filename not set.');
        }

        if (file_exists($path)) {
            $content = @file_get_contents($path);
            if ($content !== false) {
                self::$loaded[$path] = $content;
                return $content;
            }
        } else {
            throw new RuntimeException('Filename does not exist.' . $path);
        }

        return '';
    }

}
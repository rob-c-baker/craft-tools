<?php

namespace alanrogers\tools\twig\extensions;

use alanrogers\tools\services\Inline;
use craft\elements\Asset;
use craft\errors\ImageTransformException;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use RuntimeException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;
use yii\base\InvalidConfigException;

class TwigTools extends AbstractExtension
{
    public function getTests() : array
    {
        return [
            'instanceof' => new TwigTest('instanceof', [ self::class, 'isInstanceof' ])
        ];
    }

    public function getFunctions(): array
    {
        return [
            'rootRelativeUrl' => new TwigFunction(
                'rootRelativeUrl',
                [ self::class, 'rootRelativeUrl' ]
            ),
            'asset64' => new TwigFunction(
                'asset64',
                [ self::class, 'asset64']
            ),
            'image64' => new TwigFunction(
                'image64',
                [ self::class, 'image64']
            ),
            'inline' => new TwigFunction(
                'inline',
                [ Inline::class, 'inline' ],
                []
            ),
            'unset' => new TwigFunction(
                'unset',
                [ self::class, 'unset' ],
                [ 'needs_context' => true ]
            ),
            'setKeyValue' => new TwigFunction(
                'setKeyValue',
                [ self::class, 'setKeyValue' ],
                [ 'needs_context' => true ]
            ),
        ];
    }

    public function getFilters(): array
    {
        return [
            'rootRelativeUrl' => new TwigFilter(
                'rootRelativeUrl',
                [ self::class, 'rootRelativeUrl' ]
            ),
            'asset64' => new TwigFilter(
                'asset64',
                [ self::class, 'asset64']
            ),
            'image64' => new TwigFunction(
                'image64',
                [ self::class, 'image64']
            ),
            'deep_merge' => new TwigFilter(
                'deep_merge',
                [ self::class, 'deepMerge'],
                [ 'is_variadic' => true ]
            )
        ];
    }

    public static function isInstanceof(mixed $variable, string $classname) : bool
    {
        return $variable instanceof $classname;
    }

    /**
     * Produces a Root Relative URL, i.e. '/path' instead of 'https://host/path'.
     * @param string $path
     * @param array|string|null $params
     * @return string
     */
    public static function rootRelativeUrl(string $path = '', array|string|null $params = null) : string
    {
        return UrlHelper::rootRelativeUrl(UrlHelper::url($path, $params));
    }

    /**
     * Converts an Asset to a base64 string.
     * @param Asset $asset
     * @param bool $inline
     * @return string|null
     * @throws ImageTransformException
     */
    public static function asset64(Asset $asset, bool $inline = false) : ?string
    {
        // Make sure the mime type is an image.
        if (!str_starts_with($asset->getMimeType(), 'image/')) {
            // Die quietly.
            return null;
        }

        // Get the file.
        try {
            return self::image64($asset->getVolume()->getRootPath() . DIRECTORY_SEPARATOR . $asset->getPath(), $asset->getExtension(), $inline);
        } catch (InvalidConfigException $e) {
            // cannot get the file / error of some sort... Shhhh:
            return null;
        }
    }

    /**
     * converts the file at a specified path to a base64 encoded string
     * @param string $path
     * @param string $extension
     * @param bool $inline
     * @return string
     */
    public static function image64(string $path, string $extension, bool $inline=false) : string
    {
        $path = FileHelper::normalizePath($path);
        $binary = file_get_contents($path);
        // Return the string.
        return $inline ? sprintf('data:image/%s;base64,%s', $extension, base64_encode($binary)) : base64_encode($binary);
    }

    public static function deepMerge(array $array1, array $array2, array $arg = []): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                if (self::isAssoc($value)) {
                    $merged[$key] = self::deepMerge($merged[$key], $value);
                } else {
                    $merged[$key] =  [ ...$merged[$key], ...$value ];
                }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * @param array $arr
     * @return bool
     */
    private static function isAssoc(array $arr) : bool
    {
        if ([] === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * $context is a special array which holds all know variables inside
     * If $key is not defined unset the whole variable inside context
     * If $key is set test if $context[$variable] is defined if so unset $key inside multidimensional array
     **/
    public static function unset(&$context, $variable, $key = null): void
    {
        if ($key === null) {
            unset($context[$variable]);
        } else {
            if (isset($context[$variable])) {
                unset($context[$variable][$key]);
            }
        }
    }

    /**
     * @param mixed $context
     * @param string $variable_name The object / array name to set a key's value on (if not an array or object, this function is a noop)
     * @param int|string|array $key the key to use (can be a dot separated string to indicate nested keys, but only if the target is an array)
     * @param mixed $value the value to set
     * @return void
     */
    public static function setKeyValue(array &$context, string $variable_name, $key, $value) : void
    {
        // we want to be sure we are altering a reference as we want to modify the variable in place
        if (isset($context[$variable_name])) {
            $target = &$context[$variable_name];
            if (is_array($target)) {
                // Using this helper means that we can have keys like "key1.key2.key3" referring to nested array values
                // ...or a key like ['key1','key2','key3'] meaning the same thing
                ArrayHelper::setValue($target, $key, $value);
            } elseif (is_object($target)) {
                $target->$key = $value;
            }
        } else {
            throw new RuntimeException(sprintf('Cannot set a key on variable "%s" because it is undefined.', $variable_name));
        }
    }
}
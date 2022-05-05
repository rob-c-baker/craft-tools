<?php

namespace alanrogers\tools\twig\extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DeepMerge extends AbstractExtension
{
    public function getFilters(): array
    {
        $options = [
            'is_variadic' => true
        ];
        return [
            'deep_merge' => new TwigFilter(
                'deep_merge',
                [ DeepMerge::class, 'deepMerge'],
                $options
            )
        ];
    }

    public static function deepMerge(array $array1, array $array2, array $arg = []): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                if (self::isAssoc($value)) {
                    $merged[$key] = self::deepMerge($merged[$key], $value);
                } else {
                    $merged[$key] = array_merge($merged[$key], $value);
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
}
<?php
declare(strict_types=1);

namespace alanrogers\tools\helpers;

class ArrayHelper implements HelperInterface
{
    /**
     * @see https://www.codeproject.com/Questions/780780/PHP-Finding-differences-in-two-multidimensional-ar
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function diffAssocRecursive(array $array1, array $array2) : array
    {
        $difference = [];

        foreach($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key])) {
                    $difference[$key] = $value;
                } elseif (!is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = self::diffAssocRecursive($value, $array2[$key]);
                    if ($new_diff) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif (!isset($array2[$key]) || $array2[$key] != $value) {
                $difference[$key] = $value;
            }
        }

        return $difference;
    }

    /**
     * Recursive version of in_array()
     * @param $needle
     * @param array $haystack
     * @param bool $strict
     * @return bool
     */
    public static function inArrayRecursive($needle, array $haystack, bool $strict=false) : bool
    {
        foreach ($haystack as $item) {
            /** @noinspection TypeUnsafeComparisonInspection */
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && self::inArrayRecursive($needle, $item, $strict))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Move an element in the passed in array from $from_index to $to_index
     * @param array $array
     * @param int $from_index
     * @param int $to_index
     */
    public static function moveElement(array &$array, int $from_index, int $to_index): void
    {
        if ($from_index === $to_index) {
            return;
        }
        array_splice(
            $array,
            max($to_index, 0),
            0,
            array_splice($array, max($from_index, 0), 1)
        );
    }

    /**
     * Inserts an element into a numerically indexed array at the specified $position (zero indexed)
     * @param array $array
     * @param int $position
     * @param $element
     */
    public static function insertElementAtPosition(array &$array, int $position, $element): void
    {
        array_splice($array, $position, 0, [ $element ]);
        $array = array_values($array);
    }

    /**
     * Tell whether all members of $array validate the $predicate.
     *
     * all(array(1, 2, 3),   'is_int'); -> true
     * all(array(1, 2, 'a'), 'is_int'); -> false
     * @param array $array
     * @param callable $predicate
     * @return bool
     */
    public static function all(array $array, callable $predicate) : bool
    {
        return array_filter($array, $predicate) === $array;
    }

    /**
     * Tell whether any member of $array validates the $predicate.
     *
     * any(array(1, 'a', 'b'),   'is_int'); -> true
     * any(array('a', 'b', 'c'), 'is_int'); -> false
     * @param array $array
     * @param callable $predicate
     * @return bool
     */
    public static function any(array $array, callable $predicate) : bool
    {
        return array_filter($array, $predicate) !== [];
    }

    /**
     * Looks through the keys of the passed in array for keys that look like:
     *
     * 'contactNumbers[0].numberType' => 'value'
     *
     * The returned array for that would look like:
     *
     * "contactNumbers" => [
     *     0 => [
     *         "numberType" => [
     *             0 => 'value'
     *         ]
     *     ]
     * ]
     *
     * @param array $array
     * @param string|null $numeric_index_prefix
     * @param array|null $key_path
     * @return bool Whether the array was modified
     */
    public static function parseComplexKeys(array &$array, ?string $numeric_index_prefix=null, ?array &$key_path=null): bool
    {
        $modified = false;
        foreach ($array as $k => $v) {
            if (str_contains($k, '.')) {
                $parts = explode('.', $k);
                for ($p_idx = 0; $p_idx < count($parts); $p_idx++) {
                    if (str_contains($parts[$p_idx], '[')) {
                        preg_match('/(.+)\[(\d+)]/', $parts[$p_idx], $matches);
                        if (isset($matches[1], $matches[2])) {
                            $parts[$p_idx] = $matches[1];
                            $idx = $matches[2];
                            if ($numeric_index_prefix) {
                                $idx = $numeric_index_prefix . $idx;
                            }
                            array_splice($parts, $p_idx + 1, 0, $idx);
                        }
                    }
                }
                $key_path = $parts;
                \craft\helpers\ArrayHelper::setValue($array, $key_path, $v);
                unset($array[$k]);
                $modified = true;
            }
        }
        return $modified;
    }

    /**
     * Cast elements in $array identified by $keys to int (mostly used in database result sets)
     * Calls itself recursively on nested arrays
     * @param array $array
     * @param string[] $keys
     * @param bool $recursive Set to false to disable recursive-ness
     * @return array the modified $array
     */
    public static function castArrayElementsToInt(array $array, array $keys, bool $recursive = true) : array
    {
        foreach ($array as $key => &$item) {
            if ($recursive && is_array($item)) {
                $array[$key] = self::castArrayElementsToInt($item, $keys, $recursive);
            } elseif (in_array($key, $keys, true)) {
                $array[$key] = (int) $item;
            }
        }
        return $array;
    }
}
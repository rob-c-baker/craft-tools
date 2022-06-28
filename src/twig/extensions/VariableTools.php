<?php
declare(strict_types=1);

namespace alanrogers\tools\twig\extensions;

use craft\helpers\ArrayHelper;
use RuntimeException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class VariableTools extends AbstractExtension
{
    public function getFunctions() : array
    {
        return [
            new TwigFunction(
                'unset',
                [ VariableTools::class, 'unset' ],
                [ 'needs_context' => true ]
            ),
            new TwigFunction(
                'setKeyValue',
                [ VariableTools::class, 'setKeyValue' ],
                [ 'needs_context' => true ]
            ),
        ];
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
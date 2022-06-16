<?php
declare(strict_types=1);

namespace alanrogers\tools\twig\extensions;

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
                []
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
     * @param mixed $target The object / array to set a key's value on (if not an array or object, this function is a noop)
     * @param int|string $key the key to use
     * @param mixed $value the value to set
     * @return void
     */
    public static function setKeyValue(&$target, $key, $value)
    {
        if (is_array($target)) {
            $target[$key] = $value;
        } elseif (is_object($target)) {
            $target->$key = $value;
        }
    }
}
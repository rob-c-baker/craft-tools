<?php

namespace alanrogers\tools\twig\extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UnsetVariable extends AbstractExtension
{
    public function getFunctions() : array
    {
        return [
            new TwigFunction(
                'unset',
                [ $this, 'unset' ],
                [ 'needs_context' => true ]
            )
        ];
    }

    /**
     * $context is a special array which holds all know variables inside
     * If $key is not defined unset the whole variable inside context
     * If $key is set test if $context[$variable] is defined if so unset $key inside multidimensional array
     **/
    public function unset(&$context, $variable, $key = null): void
    {
        if ($key === null) {
            unset($context[$variable]);
        } else {
            if (isset($context[$variable])) {
                unset($context[$variable][$key]);
            }
        }
    }

}
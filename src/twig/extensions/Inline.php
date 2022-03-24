<?php

namespace alanrogers\tools\twig\extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Inline extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'inline',
                [ \modules\ar\services\Inline::class, 'inline' ],
                []
            )
        ];
    }
}
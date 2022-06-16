<?php
declare(strict_types=1);

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
                [ \alanrogers\tools\services\Inline::class, 'inline' ],
                []
            )
        ];
    }
}
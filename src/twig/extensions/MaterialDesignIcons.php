<?php
declare(strict_types=1);

namespace alanrogers\tools\twig\extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MaterialDesignIcons extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'mdiIcon',
                [ \alanrogers\tools\services\MaterialDesignIcons::class, 'inlineIconSVG' ],
                [ 'is_safe' => [ 'html' ] ]
            )
        ];
    }
}
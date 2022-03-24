<?php

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
                [ \modules\ar\services\MaterialDesignIcons::class, 'inlineIconSVG' ],
                [ 'is_safe' => [ 'html' ] ]
            )
        ];
    }
}
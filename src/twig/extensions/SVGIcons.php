<?php
declare(strict_types=1);

namespace alanrogers\tools\twig\extensions;

use alanrogers\tools\services\BrandIcons;
use alanrogers\tools\services\MaterialDesignIcons;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SVGIcons extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'mdiIcon',
                [ MaterialDesignIcons::class, 'inlineIconSVG' ],
                [ 'is_safe' => [ 'html' ] ]
            ),
            new TwigFunction(
                'brandIcon',
                [ BrandIcons::class, 'inlineIconSVG' ],
                [ 'is_safe' => [ 'html' ] ]
            )
        ];
    }
}
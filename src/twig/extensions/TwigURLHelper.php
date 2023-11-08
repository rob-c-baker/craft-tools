<?php

namespace alanrogers\tools\twig\extensions;

use craft\helpers\UrlHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigURLHelper extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'rootRelativeUrl',
                [ self::class, 'rootRelativeUrl' ]
            )
        ];
    }

    public function getFilters(): array
    {
        return [
            'rootRelativeUrl' => new TwigFilter(
                'rootRelativeUrl',
                [ self::class, 'rootRelativeUrl' ]
            )
        ];
    }

    /**
     * Produces a Root Relative URL, i.e. '/path' instead of 'https://host/path'.
     * @param string $path
     * @param array|string|null $params
     * @return string
     */
    public static function rootRelativeUrl(string $path = '', array|string|null $params = null) : string
    {
        return UrlHelper::rootRelativeUrl(UrlHelper::url($path, $params));
    }
}
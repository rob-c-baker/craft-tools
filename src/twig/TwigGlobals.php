<?php

namespace alanrogers\tools\twig;

use Craft;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class TwigGlobals extends AbstractExtension implements GlobalsInterface
{
    /**
     * @var bool
     */
    private bool $is_console_request;

    public function __construct()
    {
        $this->is_console_request = Craft::$app instanceof craft\console\Application;
    }

    public function getGlobals() : array
    {
        $can_use_webp = true;
        $browser_type = 'modern';

        if (!$this->is_console_request) {

            // The "X-Accept-WebP" (value: "1"|"") header is set by Varnish and used in it's hash key. It's based
            // on whether the client's "Accept" header contained "image/webp" and is used for detecting that and
            // using a different image format within the HTML throughout the site.
            $can_use_webp = (bool) Craft::$app->getRequest()->getHeaders()->get('X-Accept-WebP', false);

            // Use browser type to work out which build to use (set by nginx proxy virtual host - before Varnish)
            $browser_type_header = Craft::$app->getRequest()->getHeaders()->get('X-Browser-Type', 'modern');
            if ($browser_type_header === 'legacy' || $browser_type_header === 'modern') {
                $browser_type = $browser_type_header;
            }
        }

        return [
            'can_use_webp' => $can_use_webp,
            'browser_type' => $browser_type
        ];
    }
}
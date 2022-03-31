<?php

namespace alanrogers\tools\twig\extensions;

use craft\elements\Asset;
use craft\helpers\FileHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use yii\base\InvalidConfigException;

class Image64 extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            'asset64' => new TwigFilter(
                'asset64',
                [ $this, 'asset64']
            ),
            'image64' => new TwigFunction(
                'image64',
                [ $this, 'image64']
            )
        ];
    }

    public function getFunctions(): array
    {
        return [
            'asset64' => new TwigFunction(
                'asset64',
                [ $this, 'asset64']
            ),
            'image64' => new TwigFunction(
                'image64',
                [ $this, 'image64']
            )
        ];
    }

    /**
     * Converts an Asset to a base64 string.
     * @param Asset $asset
     * @param bool $inline
     * @return string
     */
    public function asset64(Asset $asset, bool $inline = false) : string
    {
        // Make sure the mime type is an image.
        if (0 !== strpos($asset->getMimeType(), 'image/')) {
            // Die quietly.
            return false;
        }

        // Get the file.
        try {
            return $this->image64($asset->getVolume()->getRootPath() . DIRECTORY_SEPARATOR . $asset->getPath(), $asset->getExtension(), $inline);
        } catch (InvalidConfigException $e) {
            // cannot get the file / error of some sort... Shhhh:
            return false;
        }
    }

    /**
     * converts the file at a specified path to a base64 encoded string
     * @param string $path
     * @param string $extension
     * @param bool $inline
     * @return string
     */
    public function image64(string $path, string $extension, bool $inline=false) : string
    {
        $path = FileHelper::normalizePath($path);
        $binary = file_get_contents($path);
        // Return the string.
        return $inline ? sprintf('data:image/%s;base64,%s', $extension, base64_encode($binary)) : base64_encode($binary);
    }
}
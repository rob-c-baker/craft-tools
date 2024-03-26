<?php declare(strict_types=1);

namespace alanrogers\tools\models\seo;

use DateTime;
use Override;

/**
 * This models is a little different to other SEO models. THis is not directly to do with what is rendered, but instead
 * controls how an items is represented on a sitemap, XML or otherwise.
 */
class Sitemap extends Container
{
    private bool $enabled = true;
    private string $loc = '';
    private ?DateTime $lastmod = null;
    private string $changefreq = 'weekly';
    private float $priority = 0.5;

    #[Override]
    public function fields(): array
    {
        return [
            'enabled',
            'loc',
            'lastmod',
            'changefreq',
            'priority',
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return [
            'enabled',
            'loc',
            'lastmod',
            'changefreq',
            'priority',
        ];
    }

    #[Override]
    public function render(): string
    {
        return '';
    }

    #[Override]
    public function headers(): array
    {
        return [];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getLoc(): string
    {
        return $this->loc;
    }

    public function setLoc(string $loc): static
    {
        $this->loc = $loc;
        return $this;
    }

    public function getLastmod(): ?DateTime
    {
        return $this->lastmod;
    }

    public function setLastmod(?DateTime $lastmod): static
    {
        $this->lastmod = $lastmod;
        return $this;
    }

    public function getChangeFreq(): string
    {
        return $this->changefreq;
    }

    public function setChangeFreq(string $changefreq): static
    {
        $this->changefreq = $changefreq;
        return $this;
    }

    public function getPriority(): float
    {
        return $this->priority;
    }

    public function setPriority(float $priority): static
    {
        $this->priority = $priority;
        return $this;
    }
}
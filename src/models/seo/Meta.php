<?php declare(strict_types=1);

namespace alanrogers\tools\models\seo;

use craft\helpers\Html;
use InvalidArgumentException;
use Override;

class Meta extends Container
{
    public const array ALLOWED_REFERRER_VALUES = [
        'no-referrer',
        'origin',
        'no-referrer-when-downgrade',
        'origin-when-cross-origin',
        'same-origin',
        'strict-origin',
        'strict-origin-when-cross-origin',
        'unsafe-url'
    ];

    private string $description = '';

    private string $keywords = '';

    private string $author = '';

    private string $generator = '';

    private string $referrer = self::ALLOWED_REFERRER_VALUES[2];

    #[Override]
    public function fields(): array
    {
        return [
            'description',
            'keywords',
            'author',
            'generator',
            'referrer',
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return [
            'description',
            'keywords',
            'author',
            'generator',
            'referrer',
        ];
    }

    #[Override]
    public function render(): string
    {
        $html = [];
        if ($this->description) {
            $html[] = '<meta name="description" content="' . Html::encode($this->description) . '">';
        }
        if ($this->keywords) {
            $html[] = '<meta name="keywords" content="' . Html::encode($this->keywords) . '">';
        }
        if ($this->author) {
            $html[] = '<meta name="author" content="' . Html::encode($this->author) . '">';
        }
        if ($this->generator) {
            $html[] = '<meta name="generator" content="' . Html::encode($this->generator) . '">';
        }
        if ($this->referrer) {
            $html[] = '<meta name="referrer" content="' . Html::encode($this->referrer) . '">';
        }
        return implode("\n", $html);
    }

    #[Override]
    public function headers(): array
    {
        return [];
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Comma separated
     * @param string $keywords
     * @return $this
     */
    public function setKeywords(string $keywords): static
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function setGenerator(string $generator): static
    {
        $this->generator = $generator;
        return $this;
    }

    public function setReferrer(string $referrer): static
    {
        if (!in_array($referrer, static::ALLOWED_REFERRER_VALUES)) {
            throw new InvalidArgumentException('Value must be one of ' . implode(', ', static::ALLOWED_REFERRER_VALUES));
        }
        $this->referrer = $referrer;
        return $this;
    }
}
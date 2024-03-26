<?php declare(strict_types=1);

namespace alanrogers\tools\models\seo;

use InvalidArgumentException;
use Override;

class Robots extends Container
{
    private const string DEFAULT_NAME = 'robots';

    private const array ALLOWED_VALUES = [
        'all', // There are no restrictions for indexing or serving. This rule is the default value and has no effect if explicitly listed.
        'noindex', // prevents the page from being included in the index.
        'nofollow', // prevents Googlebot from following any links on the page. (Note that this is different from the link-level nofollow attribute, which prevents Googlebot from following an individual link.)
        'noarchive', // prevents a cached copy of this page from being available in the search results.
        'nosnippet', // prevents a description from appearing below the page in the search results, as well as prevents caching of the page.
        'noodp', // blocks the Open Directory Project description of the page from being used in the description that appears below the page in the search results.
        'none', // equivalent to noindex, nofollow.
        'nositelinkssearchbox', // Do not show a sitelinks search box in the search results for this page
        'notranslate', // Don't offer translation of this page in search results
        'noimageindex', // 	Do not index images on this page.
    ];

    /**
     * This is the `name` attribute for the tag, can be set to something like `googlebot` instead of the default `robots`.
     * Forced to lowercase.
     * @var string
     */
    private string $name = self::DEFAULT_NAME;

    /**
     * Default is empty which means the tag will not render.
     * All values forced to lower case.
     * @var array|string[]
     */
    private array $values = [];

    #[Override]
    public function fields(): array
    {
        return [
            'name',
            'values',
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return [
            'name',
            'values',
        ];
    }

    /**
     * @inheritdoc
     */
    #[Override]
    public function render(): string
    {
        return $this->values ? sprintf(
            '<meta name="%s" content="%s">',
            $this->name,
            implode(',', $this->values)
        ) : '';
    }

    /**
     * @inheritdoc
     */
    #[Override]
    public function headers(): array
    {
        $headers = [];

        if ($this->values) {
            $val = '';
            if ($this->name !== self::DEFAULT_NAME) {
                $val .= $this->name . ': ';
            }
            $val .= implode(',', $this->values);
            $headers['X-Robots-Tag'] = [ $val ];
        }
        return $headers;
    }

    public function setName(string $name): static
    {
        $this->name = strtolower($name);
        return $this;
    }

    public function setValues(array $values): static
    {
        foreach ($values as $value) {
            if (!in_array($value, self::ALLOWED_VALUES)) {
                throw new InvalidArgumentException('Value must be one of ' . implode(', ', self::ALLOWED_VALUES));
            }
        }
        // by checking against our whitelist in lower case, we implicitly ensure lower case.
        $this->values = $values;
        return $this;
    }

    public function removeValue(string $value): static
    {
        return $this->setValues(array_filter($this->values, fn($v) => $v !== $value));
    }
}
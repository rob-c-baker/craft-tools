<?php declare(strict_types=1);

namespace alanrogers\tools\models\seo;

use alanrogers\tools\models\SEOFieldModel;
use craft\base\Model;
use InvalidArgumentException;
use Override;

abstract class Container extends Model
{

    public function __construct(protected SEOFieldModel $seo_field_model, array $attributes=[])
    {
        parent::__construct();

        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            } else {
                throw new InvalidArgumentException('Invalid property: ' . $key);
            }
        }
    }

    /**
     * Returns the rendered HTML for the container.
     * @return string
     */
    abstract public function render(): string;

    /**
     * Returns HTTP headers to be set in the response.
     * @return array<string, string[]>
     */
    abstract public function headers(): array;
}
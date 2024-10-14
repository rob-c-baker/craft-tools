<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use craft\elements\Entry;
use Exception;
use Override;

class ContentBuilderMatrix extends Base
{
    /**
     * Validates the various matrix block fields found in our content builder matrix field.
     * @param Entry[] $value
     * @return bool
     * @throws Exception
     */
    #[Override]
    protected function validate(mixed $value) : bool
    {
        throw new Exception('Not yet implemented.'); // @todo
    }
}
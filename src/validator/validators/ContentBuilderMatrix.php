<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use craft\elements\MatrixBlock;
use Exception;

class ContentBuilderMatrix extends Base
{
    /**
     * Validates the various matrix block fields found in our content builder matrix field.
     * @param MatrixBlock[] $value
     * @return bool
     * @throws Exception
     */
    protected function validate($value) : bool
    {
        throw new Exception('Not yet implemented.'); // @todo
    }
}
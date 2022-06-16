<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;

class VideoId extends Base
{
    /**
     * @inheritDoc
     */
    protected function validate($value): bool
    {
        $result = filter_var($value, FILTER_VALIDATE_URL);
        if ($result) {
            $this->addError('Video identifier must not be a URL. For example, it should be this: ivL6O13WbpM out of a URL like this https://www.youtube.com/watch?v=ivL6O13WbpM');
        }
        return $result === false;
    }
}
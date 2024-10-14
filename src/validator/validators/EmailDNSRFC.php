<?php declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\Extra\SpoofCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use InvalidArgumentException;
use Override;

/**
 * Checks an email for validity by checking if it is in RFC format, and if it has DNS records that allow email
 */
class EmailDNSRFC extends Base
{
    public const string CHECK_RFC = 'RFC';
    public const string CHECK_DNS = 'DNS';
    public const string CHECK_SPOOF = 'SPOOF';

    #[Override]
    protected function validate($value): bool
    {
        if (!isset($this->options['checks'])) {
            throw new \InvalidArgumentException('To use the EmailDNSRFC validator you must pass in an $options parameter to the constructor with with an array key of "checks" containing a number of the class constants formed `CHECK_*`.');
        }

        $checks = [];

        foreach ($this->options['checks'] as $check) {
            $checks[] = match ($check) {
                self::CHECK_RFC => new RFCValidation(),
                self::CHECK_DNS => new DNSCheckValidation(),
                self::CHECK_SPOOF => new SpoofCheckValidation(),
                default => throw new InvalidArgumentException('Invalid check: ' . json_encode($check)),
            };
        }

        if (!$checks) {
            throw new InvalidArgumentException('No checks specified.');
        }

        $validator = new EmailValidator();
        $validations = new MultipleValidationWithAnd($checks);
        $result = $validator->isValid($value, $validations);

        $error = $validator->getError();
        if ($error) {
            $this->addError(sprintf('[%d] %s', $error->code(), $error->description()));
        }

        return $result;
    }
}
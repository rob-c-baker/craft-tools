<?php
declare(strict_types=1);

namespace alanrogers\tools\services;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumber as PNumber;
use yii\base\Component;

class PhoneNumber extends Component
{
    public const FORMAT_E164 = PhoneNumberFormat::E164;
    public const FORMAT_NATIONAL = PhoneNumberFormat::NATIONAL;
    public const FORMAT_INTERNATIONAL = PhoneNumberFormat::INTERNATIONAL;

    private PhoneNumberUtil $phone_util;

    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->phone_util = PhoneNumberUtil::getInstance();
    }

    /**
     * @param string $number
     * @param int $country_code
     * @return PNumber|null
     */
    public function fromNumberAndCountryCode(string $number, int $country_code) : ?PNumber
    {
        $n = new PNumber();
        $n->setNationalNumber($number);
        $n->setCountryCode($country_code);
        if ($this->phone_util->isValidNumber($n)) {
            return $n;
        }
        return null;
    }

    /**
     * @param string $number
     * @return int|null
     */
    public function extractCountryCode(string $number) : ?int
    {
        $n = new PNumber();
        try {
            $country_code = $this->phone_util->maybeExtractCountryCode(
                $number,
                null,
                $national_number,
                false,
                $n
            );
            if ($country_code) {
                return $country_code;
            }
        } catch (NumberParseException $e) {
            // fall through and return null - could not get a country code.
        }
        return null;
    }

    /**
     * Gets an ISO 2 characte rcountry code from the numeric country dialing code
     * @param int $country_code
     * @return string|null
     */
    public function getISO2FromCountryCode(int $country_code) : ?string
    {
        $code = $this->phone_util->getRegionCodeForCountryCode($country_code);
        return $code === 'ZZ' ? null : $code;
    }

    /**
     * @param string $number
     * @param string $iso2 ISO 2 char country code
     * @return PNumber
     * @throws NumberParseException
     */
    public function parse(string $number, string $iso2) : PNumber
    {
        return $this->phone_util->parse($number, $iso2);
    }

    /**
     * @param PNumber $number
     * @param int $format A FORMAT_* class constant
     * @return string
     */
    public function format(PNumber $number, int $format) : string
    {
        return $this->phone_util->format($number, $format);
    }

    /**
     * @param string $number
     * @param string $iso2 ISO 2 char country code
     * @return bool
     */
    public function validateFromString(string $number, string $iso2) : bool
    {
        try {
            $num = $this->parse($number, $iso2);
        } catch (NumberParseException $e) {
            return false;
        }

        return $this->validateFromNumberObject($num);
    }

    /**
     * @param PNumber $number
     * @return bool
     */
    public function validateFromNumberObject(PNumber $number) : bool
    {
        return $this->phone_util->isValidNumber($number);
    }
}
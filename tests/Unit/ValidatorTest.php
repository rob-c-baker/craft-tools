<?php

namespace Tests\Unit;

use alanrogers\tools\validator\Factory;
use alanrogers\tools\validator\validators\ARRef;
use alanrogers\tools\validator\validators\CountryISOCode;
use alanrogers\tools\validator\validators\Email;
use alanrogers\tools\validator\validators\Integer;
use alanrogers\tools\validator\validators\ISBN;
use alanrogers\tools\validator\validators\Latitude;
use alanrogers\tools\validator\validators\Longitude;
use alanrogers\tools\validator\validators\MaxLength;
use alanrogers\tools\validator\validators\MinLength;
use alanrogers\tools\validator\validators\Password;
use alanrogers\tools\validator\validators\UUID4;
use stdClass;
use Tests\Support\UnitTester;

class ValidatorTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    protected function _before()
    {
    }

    public function testIntegerValidator()
    {
        $validator = Factory::create(Integer::class);

        $this->assertTrue($validator->setValue(0)->isValid(), 'Checking 0');

        $this->assertTrue($validator->setValue(1)->isValid(), 'Checking 1');

        $this->assertTrue($validator->setValue(-1)->isValid(), 'Checking -1');

        $this->assertFalse($validator->setValue('')->isValid(), 'Checking empty string');

        $this->assertFalse($validator->setValue('test')->isValid(), 'Checking non-empty string');

        $this->assertFalse($validator->setValue([])->isValid(), 'Checking empty array');

        $this->assertFalse($validator->setValue([ 1, 2, 3 ])->isValid(), 'Checking non-empty array');

        $this->assertFalse($validator->setValue(new stdClass())->isValid(), 'Checking object');
    }

    public function testARRefValidator()
    {
        $validator = Factory::create(ARRef::class);

        $this->assertInstanceOf(ARRef::class, $validator, 'Correct type for ARRef validator');

        $this->assertTrue($validator->setValue('FR29010')->isValid(), 'Checking a valid AR Ref');
        $this->assertFalse($validator->setValue('  FR29010 ')->isValid(), 'Checking a valid AR Ref with spaces');
        $this->assertFalse($validator->setValue('0893ch')->isValid(), 'Checking a invalid AR Ref');
    }

    public function testISO2CountryCode()
    {
        $validator = Factory::create(CountryISOCode::class, 'GB', [
            'standard' => CountryISOCode::STANDARD_ISO2
        ]);
        $this->assertTrue($validator->isValid(), 'Valid uppercase 2 char country code');

        $validator = Factory::create(CountryISOCode::class, 'gb', [
            'standard' => CountryISOCode::STANDARD_ISO2
        ]);
        $this->assertFalse($validator->isValid(), 'Invalid lowercase 2 char country code');

        $validator = Factory::create(CountryISOCode::class, '1L', [
            'standard' => CountryISOCode::STANDARD_ISO2
        ]);
        $this->assertFalse($validator->isValid(), 'Invalid 2 char country code');
    }

    public function testISO3CountryCode()
    {
        $validator = Factory::create(CountryISOCode::class, 'GBR', [
            'standard' => CountryISOCode::STANDARD_ISO3
        ]);
        $this->assertTrue($validator->isValid(), 'Valid uppercase 3 char country code');

        $validator = Factory::create(CountryISOCode::class, 'gbr', [
            'standard' => CountryISOCode::STANDARD_ISO3
        ]);
        $this->assertFalse($validator->isValid(), 'Invalid lowercase 3 char country code');

        $validator = Factory::create(CountryISOCode::class, '1L4', [
            'standard' => CountryISOCode::STANDARD_ISO3
        ]);
        $this->assertFalse($validator->isValid(), 'Invalid 3 char country code');
    }

    public function testEmailValidator()
    {
        $valid_emails = [
            'test@example.com' => 'Valid email',
            'firstname.lastname@example.com' => 'The email contains a dot in the address field',
            'email@subdomain.example.com' => 'The email contains a dot with a subdomain',
            'firstname+lastname@example.com' => 'Plus sign is considered a valid character',
            '"email"@example.com' => 'Quotes around email are considered valid',
            '1234567890@example.com' => 'Digits in the address are valid',
            'email@example-one.com' => 'Dash in the domain name is valid',
            '_______@domain.com' => 'Underscore in the address field is valid',
            'email@domain.name' => '.name is a valid Top Level Domain name',
            'email@domain.co.jp' => 'Dot in Top Level Domain name also considered valid',
            'firstname-lastname@domain.com' => 'Dash in the address field is valid'
        ];

        $invalid_emails = [
            'plain address' => 'Missing @ sign and domain',
            '#@%^%#$@#$@#.com' => 'Garbage',
            '@domain.com' => 'Missing username',
            'Joe Smith <email@domain.com>' => 'Encoded HTML within an email is invalid',
            'email.domain.com' => 'Missing @',
            'email@domain@domain.com' => 'Two @ sign',
            '.email@domain.com' => 'The leading dot in the address is not allowed',
            'email.@domain.com' => 'Trailing dot in address is not allowed',
            'email..email@domain.com' => 'Multiple dots',
            'email@domain.com (Joe Smith)' => 'Text followed email is not allowed',
            'email@domain' => 'Missing top-level domain (.com/.net/.org/etc.)',
            'email@-domain.com' => 'The leading dash in front of the domain is invalid',
            'email@domain..com' => 'Multiple dots in the domain portion is invalid'
        ];

        $validator = Factory::create(Email::class);

        foreach ($valid_emails as $email => $message) {
            $this->assertTrue($validator->setValue($email)->isValid(), $message);
        }

        foreach ($invalid_emails as $email => $message) {
            $this->assertFalse($validator->setValue($email)->isValid(), $message);
        }
    }

    public function testLatLngValidators()
    {
        $lat_validator = Factory::create(Latitude::class);
        $lng_validator = Factory::create(Longitude::class);

        $this->assertTrue($lat_validator->setValue(0)->isValid(), 'Zero Latitude');
        $this->assertTrue($lng_validator->setValue(0)->isValid(), 'Zero Longitude');

        $this->assertTrue($lat_validator->setValue(-90.0)->isValid(), 'Min Latitude');
        $this->assertTrue($lat_validator->setValue(90.0)->isValid(), 'Max Latitude');

        $this->assertTrue($lng_validator->setValue(-180.0)->isValid(), 'Min Longitude');
        $this->assertTrue($lng_validator->setValue(180.0)->isValid(), 'Max Longitude');

        $this->assertFalse($lat_validator->setValue(-91.0)->isValid(), 'Out of bounds - Latitude');
        $this->assertFalse($lat_validator->setValue(91.0)->isValid(), 'Out of bounds + Latitude');

        $this->assertFalse($lng_validator->setValue(-181.0)->isValid(), 'Out of bounds - Longitude');
        $this->assertFalse($lng_validator->setValue(181.0)->isValid(), 'Out of bounds + Longitude');

        $this->assertFalse($lng_validator->setValue('fasdfasdfsadf')->isValid(), 'Random string');
        $this->assertFalse($lng_validator->setValue('')->isValid(), 'Empty string');
    }

    public function testMinMaxLengthValidators()
    {
        $max_validator = Factory::create(MaxLength::class, null, [
            'length' => 5
        ]);

        $min_validator = Factory::create(MinLength::class, null, [
            'length' => 5
        ]);

        $this->assertTrue($max_validator->setValue('abcde')->isValid(), 'Valid max length');
        $this->assertFalse($max_validator->setValue('abcdef')->isValid(), 'Invalid max length');

        $this->assertTrue($min_validator->setValue('abcde')->isValid(), 'Valid min length');
        $this->assertFalse($min_validator->setValue('abcd')->isValid(), 'Invalid min length');
    }

    public function testUUID4Validator()
    {
        $validator = Factory::create(UUID4::class);

        $this->assertTrue($validator->setValue('5f094fc7-bbb7-436f-9b3a-a743d2ae1bd1')->isValid(), 'Valid UUID4');
        $this->assertFalse($validator->setValue('5f094fc7-bbb7-f36f-9b3a-a743d2ae1bd1')->isValid(), 'Invalid UUID4');
        $this->assertFalse($validator->setValue('suigfvbf-9qwf-akjs-0ajh-jasfjkasdkjh')->isValid(), 'Random string');
        $this->assertFalse($validator->setValue('')->isValid(), 'Empty string');
    }

    public function testPasswordValidator()
    {
        $validator = Factory::create(Password::class, null, [
            'min_length' => 8,
            'max_length' => 12,
            'check_pwned_db' => false
        ]);

        $validator->setOptionProperties();

        $validator->setOptions([
            'check_case' => false,
            'check_numbers' => false,
            'check_symbols' => false
        ])->setOptionProperties();
        $this->assertTrue($validator->setValue('aaaaaaaa')->isValid(), 'Weak test');

        $validator->setOptions([
            'check_case' => true,
            'check_numbers' => false,
            'check_symbols' => false
        ])->setOptionProperties();
        $this->assertTrue($validator->setValue('aaaAaaaa')->isValid(), 'Check case OK');
        $this->assertFalse($validator->setValue('aaaaaaaa')->isValid(), 'Check case Bad');

        $validator->setOptions([
            'check_case' => false,
            'check_numbers' => true,
            'check_symbols' => false
        ])->setOptionProperties();
        $this->assertTrue($validator->setValue('aaa1aaaa')->isValid(), 'Check numbers OK');
        $this->assertFalse($validator->setValue('aaaaaaaa')->isValid(), 'Check numbers Bad');

        $validator->setOptions([
            'check_case' => false,
            'check_numbers' => false,
            'check_symbols' => true
        ])->setOptionProperties();
        $this->assertTrue($validator->setValue('aaa!aaaa')->isValid(), 'Check symbols OK');
        $this->assertFalse($validator->setValue('aaaaaaaa')->isValid(), 'Check symbols Bad');

        // defaults
        $validator->setOptions([
            'min_length' => 8,
            'max_length' => 12,
            'check_case' => true,
            'check_numbers' => true,
            'check_symbols' => true
        ])->setOptionProperties();

        $this->assertTrue($validator->setValue('asL!k8fh')->isValid(), 'Valid min length');
        $this->assertTrue($validator->setValue('asLdk8fh*H31')->isValid(), 'Valid max length');

        $this->assertFalse($validator->setValue('asLdkffh')->isValid(), 'Invalid min length');
        $this->assertFalse($validator->setValue('asLdkafhpHddddd')->isValid(), 'Invalid max length');
    }

    public function testISBNValidator()
    {
        // Examples from @link https://en.wikipedia.org/wiki/ISBN

        // Basic examples
        $validator = Factory::create(ISBN::class);
        $this->assertFalse($validator->setValue('')->isValid(), 'Check empty string is invalid.');

        // 10 digit
        $validator_10 = Factory::create(ISBN::class, null, [
            'variant' => ISBN::VARIANT_10_DIGIT
        ]);
        $this->assertTrue($validator_10->setValue('0-306-40615-2')->isValid(), 'Check valid ISBN 10 digit.');
        $this->assertFalse($validator_10->setValue('0-306-40614-2')->isValid(), 'Check invalid ISBN 10 digit.');
        $this->assertFalse($validator_10->setValue('978-0-306-40615-7')->isValid(), 'Check valid ISBN 13 digit (with 10 digit validator).');

        // 13 digit
        $validator_13 = Factory::create(ISBN::class, null, [
            'variant' => ISBN::VARIANT_13_DIGIT
        ]);
        $this->assertTrue($validator_13->setValue('978-0-306-40615-7')->isValid(), 'Check valid ISBN 13 digit.');
        $this->assertFalse($validator_13->setValue('978-0-306-40616-7')->isValid(), 'Check invalid ISBN 13 digit.');
        $this->assertFalse($validator_13->setValue('0-306-40615-2')->isValid(), 'Check valid ISBN 10 digit (with 13 digit validator).');
    }
}
<?php

namespace Tests\Unit;

use alanrogers\tools\validator\Base;
use alanrogers\tools\validator\Factory;
use alanrogers\tools\validator\validators\ARRef;
use alanrogers\tools\validator\validators\CountryISOCode;
use alanrogers\tools\validator\validators\Email;
use alanrogers\tools\validator\validators\Integer;
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

        $validator->setValue(0);
        $this->assertTrue($validator->isValid(), 'Checking 0');

        $validator->setValue(1);
        $this->assertTrue($validator->isValid(), 'Checking 1');

        $validator->setValue(-1);
        $this->assertTrue($validator->isValid(), 'Checking -1');

        $validator->setValue('');
        $this->assertFalse($validator->isValid(), 'Checking empty string');

        $validator->setValue('test');
        $this->assertFalse($validator->isValid(), 'Checking non-empty string');

        $validator->setValue([]);
        $this->assertFalse($validator->isValid(), 'Checking empty array');

        $validator->setValue([ 1, 2, 3 ]);
        $this->assertFalse($validator->isValid(), 'Checking non-empty array');

        $validator->setValue(new stdClass());
        $this->assertFalse($validator->isValid(), 'Checking object');
    }

    public function testARRefValidator()
    {
        $validator = Factory::create(ARRef::class);

        $this->assertInstanceOf(ARRef::class, $validator, 'Correct type for ARRef validator');

        $validator->setValue('FR29010');
        $this->assertTrue($validator->isValid(), 'Checking a valid AR Ref');

        $validator->setValue('  FR29010 ');
        $this->assertFalse($validator->isValid(), 'Checking a valid AR Ref with spaces');

        $validator->setValue('0893ch');
        $this->assertFalse($validator->isValid(), 'Checking a invalid AR Ref');
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
            $validator->setValue($email);
            $this->assertTrue($validator->isValid(), $message);
        }

        foreach ($invalid_emails as $email => $message) {
            $validator->setValue($email);
            $this->assertFalse($validator->isValid(), $message);
        }
    }
}
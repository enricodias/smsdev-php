<?php

namespace enricodias\SmsDev\Tests\Validator;

use enricodias\SmsDev\Exceptions\InvalidPhoneNumberException;
use enricodias\SmsDev\Validator\PhoneNumberValidator;
use PHPUnit\Framework\TestCase;

final class PhoneNumberValidatorTest extends TestCase
{
    /**
     * @dataProvider validPhoneProvider
     */
    public function testValidatesValidNumbers($number)
    {
        $validator = new PhoneNumberValidator();

        $this->assertIsInt($validator->validate($number));
    }

    /**
     * @codeCoverageIgnore
     */
    public function validPhoneProvider()
    {
        return [
            'with country code'    => ['5511988887777'],
            'without country code' => ['11988887777'],
            'as interger'          => [11988887777],
        ];
    }

    /**
     * @dataProvider invalidPhoneProvider
     */
    public function testInvalidNumbers(string $number)
    {
        $validator = new PhoneNumberValidator();

        $this->expectException(InvalidPhoneNumberException::class);

        $validator->validate($number);
    }

    /**
     * @codeCoverageIgnore
     */
    public function invalidPhoneProvider()
    {
        return [
            // invalid input
            [''],
            ['a'],

            // without or invalid DDD
            ['88887777'],
            ['988887777'],
            ['1988887777'],
            ['088887777'],
            ['0088887777'],
            ['0188887777'],

            // invalid country code
            ['51188887777'],
            ['511988887777'],

            // not brazilian country code
            ['9911988887777'],

            // old 8 digit format
            ['1188887777'],

            // fixed line
            ['1133337777'],
            ['551133337777'],

            // too long
            ['55119888877777'],
        ];
    }
}

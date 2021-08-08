<?php

namespace enricodias\SmsDev\Tests;

/**
 * Test the optional phone number verification
 *
 * @see https://github.com/giggsey/libphonenumber-for-php libphonenumber for PHP repository.
 */
final class PhoneNumberVerificationTest extends SmsDevMock
{
    /**
     * @dataProvider validPhoneProvider
     */
    public function testValidPhoneNumbers($number)
    {
        $SmsDev = $this->getServiceMock('{"situacao": "OK", "codigo": "1", "id": "637849052", "descricao": "MENSAGEM NA FILA" }');

        $this->assertTrue($SmsDev->send($number, 'Message'));
    }

    /**
     * @codeCoverageIgnore
     */
    public function validPhoneProvider()
    {
        return [

            // with country code
            ['5511988887777'],

            // without country code
            ['11988887777'],

        ];
    }

    /**
     * @dataProvider invalidPhoneProvider
     */
    public function testInvalidPhoneNumbers($number)
    {
        $SmsDev = $this->getServiceMock();

        $this->assertFalse($SmsDev->send($number, 'Message'));
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
            [false],
            [null],
            [true],

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

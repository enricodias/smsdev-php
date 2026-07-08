<?php

namespace enricodias\SmsDev\Tests\DateTime;

use enricodias\SmsDev\DateTime\ApiDateConverter;
use PHPUnit\Framework\TestCase;

final class ApiDateConverterTest extends TestCase
{
    /**
     * Example: 2020-01-02 01:00:00 UTC should be day 2020-01-01 in America/Sao_Paulo.
     */
    public function testToApiFormatConvertsTimezone()
    {
        \date_default_timezone_set('UTC');

        $date = new \DateTimeImmutable('2020-01-02 01:00:00');

        $this->assertSame('01/01/2020', ApiDateConverter::toApiFormat($date));
    }

    /**
     * Example: 21/10/2019 11:08:58 in America/Sao_Paulo should be 14:08:58 in UTC.
     */
    public function testFromApiFormatConvertsTimezone()
    {
        \date_default_timezone_set('UTC');

        $date = ApiDateConverter::fromApiFormat('21/10/2019 11:08:58');

        $this->assertSame('2019-10-21 14:08:58', $date->format('Y-m-d H:i:s'));
    }

    /**
     * Example: 2020-01-01 (date-only, midnight in America/Sao_Paulo) should be
     * 2020-01-01 03:00:00 in UTC.
     */
    public function testFromApiFormatWithDateOnlyFormat()
    {
        \date_default_timezone_set('UTC');

        $date = ApiDateConverter::fromApiFormat('01/01/2020', '!d/m/Y');

        $this->assertSame('2020-01-01 03:00:00', $date->format('Y-m-d H:i:s'));
    }

    public function testFromApiFormatReturnsNullOnInvalidDate()
    {
        $date = ApiDateConverter::fromApiFormat('Invalid Date');

        $this->assertNull($date);
    }
}

<?php

namespace enricodias\SmsDev\Tests\Filter;

use enricodias\SmsDev\Filter\Filter;
use PHPUnit\Framework\TestCase;

final class FilterTest extends TestCase
{
    public function testDefaultFilterReturnsAllMessages()
    {
        $filter = Filter::create();

        $this->assertSame(['status' => 1], $filter->toArray());
    }

    public function testIsUnread()
    {
        $filter = Filter::create()->isUnread();

        $this->assertSame(['status' => 0], $filter->toArray());
    }

    /**
     * @dataProvider byIdProvider
     */
    public function testById(int $id, array $expected)
    {
        $filter = Filter::create()->byId($id);

        $this->assertSame($expected, $filter->toArray());
    }

    /**
     * @codeCoverageIgnore
     */
    public function byIdProvider()
    {
        return [
            'valid id' => [2515974, ['status' => 1, 'id' => 2515974]],
            'zero'     => [0, ['status' => 1]],
            'negative' => [-1, ['status' => 1]],
        ];
    }

    public function testDateBetween()
    {
        $filter = Filter::create()
            ->setDateFormat('Y-m-d')
            ->dateBetween('2018-01-19', '2019-01-19');

        $this->assertSame([
            'status' => 1,
            'date_from' => '19/01/2018',
            'date_to' => '19/01/2019',
        ], $filter->toArray());
    }

    public function testDateFromWithInvalidDateIsIgnored()
    {
        $filter = Filter::create()
            ->setDateFormat('Y-m-d')
            ->dateFrom('2018-19');

        $this->assertSame(['status' => 1], $filter->toArray());
    }

    public function testDateToWithInvalidDateIsIgnored()
    {
        $filter = Filter::create()
            ->setDateFormat('Y-m-d')
            ->dateTo('2019-01');

        $this->assertSame(['status' => 1], $filter->toArray());
    }

    public function testDateBetweenInvalidDateIsIgnored()
    {
        $filter = Filter::create()
            ->dateBetween('2018-19', '2019-01-19');

        $this->assertSame(['status' => 1], $filter->toArray());
    }

    public function testDefaultDateFormatIsUnixTimestamp()
    {
        $filter = Filter::create()->dateFrom('1546311600');

        $this->assertSame(['status' => 1, 'date_from' => '01/01/2019'], $filter->toArray());
    }

    /**
     * Test the timezone calculations in the date filters.
     *
     * Example: 2020-01-02 01:00:00 UTC should be day 2020-01-01 in America/Sao_Paulo.
     */
    public function testDateFromConvertsTimezone()
    {
        \date_default_timezone_set('UTC');

        $filter = Filter::create()
            ->setDateFormat('Y-m-d H:i:s')
            ->dateFrom('2020-01-02 01:00:00');

        $this->assertSame(['status' => 1, 'date_from' => '01/01/2020'], $filter->toArray());
    }
}

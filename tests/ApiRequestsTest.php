<?php

namespace enricodias\SmsDev\Tests;

use enricodias\SmsDev\Filter\Filter;

/**
 * Test if the requests sent are compatible with the API specification.
 *
 * @see https://www.smsdev.com.br/ SMSDev API specification.
 */
final class ApiRequestsTest extends SmsDevMock
{
    public function testApiKeyIsSent()
    {
        $this->getServiceMock('{"situacao":"OK"}', 'api_key')->getBalance();

        $this->assertSame('api_key', $this->getRequestBody()->key);
    }

    public function testGetBalance()
    {
        $SmsDev = $this->getServiceMock('{"situacao":"OK","saldo_sms":"0","descricao":"SALDO ATUAL"}');

        $SmsDev->getBalance();

        $this->assertSame('/v1/balance', $this->getRequestPath());
    }

    public function testSend()
    {
        $SmsDev = $this->getServiceMock('{"situacao": "OK"}');

        $SmsDev->setNumberValidation(false);

        $SmsDev->send('5511988887777', 'Message');

        $this->assertSame('/v1/send', $this->getRequestPath());

        $query = $this->getRequestBody();

        $this->assertEquals('',              $query->key);
        $this->assertEquals('9',             $query->type);
        $this->assertEquals('5511988887777', $query->number);
        $this->assertEquals('Message',       $query->msg);
    }

    public function testSendWithRefer()
    {
        $SmsDev = $this->getServiceMock('{"situacao": "OK"}');

        $SmsDev->setNumberValidation(false);

        $SmsDev->send('5511988887777', 'Message', 'Refer string');

        $this->assertSame('/v1/send', $this->getRequestPath());

        $query = $this->getRequestBody();

        $this->assertEquals('',              $query->key);
        $this->assertEquals('9',             $query->type);
        $this->assertEquals('5511988887777', $query->number);
        $this->assertEquals('Message',       $query->msg);
        $this->assertEquals('Refer string',  $query->refer);
    }

    public function testCancel()
    {
        $SmsDev = $this->getServiceMock('{"situacao": "OK"}');

        $SmsDev->cancel(9999999);

        $this->assertSame('/v1/cancel', $this->getRequestPath());

        $query = $this->getRequestBody();

        $this->assertEquals('', $query->key);
        $this->assertEquals(9999999, $query->id);
    }

    public function testCancelWithMultipleIds()
    {
        $SmsDev = $this->getServiceMock('[{"situacao": "OK"}]');

        $SmsDev->cancel([9999999, 8888888]);

        $this->assertSame('/v1/cancel', $this->getRequestPath());

        $query = $this->getRequestBody();

        $this->assertEquals('', $query->key);
        $this->assertEquals([9999999, 8888888], $query->id);
    }

    public function testGetStatus()
    {
        $SmsDev = $this->getServiceMock('{"situacao": "OK"}');

        $SmsDev->getStatus(9999999);

        $this->assertSame('/v1/dlr', $this->getRequestPath());

        $query = $this->getRequestBody();

        $this->assertEquals('', $query->key);
        $this->assertEquals(9999999, $query->id);
    }

    public function testGetReport()
    {
        $SmsDev = $this->getServiceMock('{"situacao": "OK"}');

        $dateFrom = new \DateTimeImmutable('2020-01-01', new \DateTimeZone('America/Sao_Paulo'));
        $dateTo   = new \DateTimeImmutable('2020-01-30', new \DateTimeZone('America/Sao_Paulo'));

        $SmsDev->getReport($dateFrom, $dateTo);

        $this->assertSame('/v1/report/total', $this->getRequestPath());

        $query = $this->getRequestBody();

        $this->assertEquals('', $query->key);
        $this->assertEquals('01/01/2020', $query->date_from);
        $this->assertEquals('30/01/2020', $query->date_to);
    }

    public function testSetFilter()
    {
        $SmsDev = $this->getServiceMock();

        $SmsDev->setFilter(
            Filter::create()
                ->isUnread()
                ->byId(2515974)
                ->setDateFormat('Y-m-d')
                ->dateBetween('2018-01-19', '2019-01-19')
        )->fetch();

        $this->assertSame('/v1/inbox', $this->getRequestPath());

        $query = $this->getRequestBody(0);

        $this->assertEquals('',        $query->key);
        $this->assertEquals('0', $query->status);
        $this->assertEquals('2515974', $query->id);
        $this->assertEquals('19/01/2018', $query->date_from);
        $this->assertEquals('19/01/2019', $query->date_to);

        $SmsDev->setFilter(
            Filter::create()
                ->setDateFormat('U')
                ->dateFrom(1546311600)
                ->dateTo(1546311600)
        )->fetch();

        $query = $this->getRequestBody(1);

        $this->assertObjectNotHasProperty('id', $query);
        $this->assertEquals('',        $query->key);
        $this->assertEquals(1, $query->status);
        $this->assertEquals('01/01/2019', $query->date_from);
        $this->assertEquals('01/01/2019', $query->date_to);
    }
}

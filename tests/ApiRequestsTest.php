<?php

namespace enricodias\SmsDev\Tests;

use enricodias\SmsDev\SmsDev;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Middleware;

/**
 * Test if the requests sent are compatible with the API specification.
 * 
 * @see https://www.smsdev.com.br/ SMSDev API specification.
 */
final class ApiRequestsTest extends SmsDevMock
{
    public function testGetBalance()
    {
        $SmsDev = $this->getServiceMock();

        $SmsDev->getBalance();

        $this->assertSame('/get', $this->_container[0]['request']->getUri()->getPath());

        $query = $this->_container[0]['request']->getHeaders()['query'];

        $this->assertSame('',      $query['key']);
        $this->assertSame('saldo', $query['action']);
    }

    public function testSend()
    {
        $SmsDev = $this->getServiceMock();

        $SmsDev->send('5511988887777', 'Message');

        $this->assertSame('/send', $this->_container[0]['request']->getUri()->getPath());

        $query = $this->_container[0]['request']->getHeaders()['query'];

        $this->assertSame('',              $query['key']);
        $this->assertSame('9',             $query['type']);
        $this->assertSame('5511988887777', $query['number']);
        $this->assertSame('Message',       $query['msg']);
    }

    public function testFilterByUnread()
    {
        $SmsDev = $this->getServiceMock();

        $SmsDev->setDateFormat('Y-m-d H:i:s')
            ->setFilter()
                ->isUnread()
            ->fetch();

        $this->assertSame('/get', $this->_container[0]['request']->getUri()->getPath());

        $query = $this->_container[0]['request']->getHeaders()['query'];

        $this->assertSame('',  $query['key']);
        $this->assertSame('0', $query['status']);
    }

    public function testFilterById()
    {
        $SmsDev = $this->getServiceMock();

        $SmsDev->setFilter()
                    ->byId(2515974)
                ->fetch();

        $this->assertSame('/get', $this->_container[0]['request']->getUri()->getPath());
        
        $query = $this->_container[0]['request']->getHeaders()['query'];

        $this->assertSame('',        $query['key']);
        $this->assertSame('2515974', $query['id']);
    }

    public function testFilterByDateBetween()
    {
        $SmsDev = $this->getServiceMock();

        $SmsDev->setDateFormat('Y-m-d')
            ->setFilter()
                ->dateBetween('2018-01-19', '2019-01-19')
            ->fetch();

        $this->assertSame('/get', $this->_container[0]['request']->getUri()->getPath());

        $query = $this->_container[0]['request']->getHeaders()['query'];

        $this->assertSame('',           $query['key']);
        $this->assertSame('19/01/2018', $query['date_from']);
        $this->assertSame('19/01/2019', $query['date_to']);
    }

    public function testFilterByDateBetween_InvalidDate()
    {
        $SmsDev = $this->getServiceMock();

        $SmsDev->setDateFormat('Y-m-d')
            ->setFilter()
                ->dateBetween('2018-19', '2019-01-19')
            ->fetch();

        $this->assertSame('/get', $this->_container[0]['request']->getUri()->getPath());

        $query = $this->_container[0]['request']->getHeaders()['query'];
        
        $this->assertSame('', $query['key']);
        $this->assertArrayNotHasKey('date_from', $query);
        $this->assertSame('19/01/2019', $query['date_to']);
        
        $SmsDev = $this->getServiceMock();

        $SmsDev->setDateFormat('Y-m-d')
            ->setFilter()
                ->dateBetween('2018-01-19', '2019-01')
            ->fetch();

        $this->assertSame('/get', $this->_container[0]['request']->getUri()->getPath());

        $query = $this->_container[0]['request']->getHeaders()['query'];

        $this->assertSame('', $query['key']);
        $this->assertSame('19/01/2018', $query['date_from']);
        $this->assertArrayNotHasKey('date_to', $query);
    }

    public function testFilterByDate()
    {
        $SmsDev = $this->getServiceMock();

        $SmsDev->setDateFormat('U')
            ->setFilter()
                ->dateTo(1546311600)
            ->fetch();

        $this->assertSame('/get', $this->_container[0]['request']->getUri()->getPath());

        $query = $this->_container[0]['request']->getHeaders()['query'];

        $this->assertSame('', $query['key']);
        $this->assertArrayNotHasKey('date_from', $query);
        $this->assertSame('01/01/2019', $query['date_to']);

        $SmsDev = $this->getServiceMock();

        $SmsDev->setDateFormat('U')
            ->setFilter()
                ->dateFrom(1546311600)
            ->fetch();

        $this->assertSame('/get', $this->_container[0]['request']->getUri()->getPath());

        $query = $this->_container[0]['request']->getHeaders()['query'];

        $this->assertSame('', $query['key']);
        $this->assertSame('01/01/2019', $query['date_from']);
        $this->assertArrayNotHasKey('date_to', $query);
    }

    /**
     * Test the timezone calculations in the date filters.
     * 
     * Example: 2020-01-02 01:00:00 should be day 2020-01-01 in America/Sao_Paulo
     */
    public function testTimezoneDate()
    {
        $SmsDev = $this->getServiceMock();

        date_default_timezone_set('UTC');

        $SmsDev->setDateFormat('Y-m-d H:i:s')
            ->setFilter()
                ->dateFrom('2020-01-02 01:00:00')
            ->fetch();

        $this->assertSame('/get', $this->_container[0]['request']->getUri()->getPath());

        $query = $this->_container[0]['request']->getHeaders()['query'];

        $this->assertSame('01/01/2020', $query['date_from']);
    }
}
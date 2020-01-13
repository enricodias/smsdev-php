<?php

namespace enricodias\SmsDev\Tests;

use enricodias\SmsDev\SmsDev;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Middleware;

/**
 * Test timezone translations to and from the API.
 * 
 * The API uses only the timezone America/Sao_Paulo. This test needs to make sure that
 * it's possible to use a different timezone in the client and get consistent results.
 * 
 * @see https://www.smsdev.com.br/ SMSDev API specification.
 */
final class TimezoneTest extends SmsDevMock
{
    /**
     * Test the timezone calculations in the date filters.
     * 
     * Example: 2020-01-02 01:00:00 should be day 2020-01-01 in America/Sao_Paulo
     */
    public function testRequestDate()
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

    /**
     * Test the timezone calculations done in the API response.
     * 
     * Example: 2018-01-19 11:35:14 in America/Sao_Paulo is 2018-01-19 13:35:14 in UTC.
     */
    public function testResponseDate()
    {
        $apiResponse = '[{"situacao":"OK","data_read":"19\/01\/2018 11:35:14","telefone":"5511988887777","id":"","refer_id":"","nome":"","msg_sent":"","id_sms_read":"2515974","descricao":"Resposta"}]';

        $SmsDev = $this->getServiceMock($apiResponse);

        date_default_timezone_set('UTC');

        $SmsDev->setDateFormat('Y-m-d H:i:s')
            ->setFilter()
                ->isUnread()
            ->fetch();

        $parsedMessages = current($SmsDev->parsedMessages());
        
        $this->assertSame('2018-01-19 13:35:14', $parsedMessages['date']);
        $this->assertSame('5511988887777',       $parsedMessages['number']);
        $this->assertSame('Resposta',            $parsedMessages['message']);
    }
}
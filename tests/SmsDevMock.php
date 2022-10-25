<?php

namespace enricodias\SmsDev\Tests;

use enricodias\SmsDev;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Middleware;

abstract class SmsDevMock extends TestCase
{
    protected $_container = [];

    public function getServiceMock($apiResponse = '')
    {
        \date_default_timezone_set('UTC');

        $this->_container = [];

        $mock = new \GuzzleHttp\Handler\MockHandler(
            [
                new \GuzzleHttp\Psr7\Response(
                    200,
                    [],
                    $apiResponse
                ),
            ]
        );

        $history = Middleware::history($this->_container);

        $handlerStack = \GuzzleHttp\HandlerStack::create($mock);
        $handlerStack->push($history);

        $client = new \GuzzleHttp\Client([
            'handler' => $handlerStack
        ]);

        $stub = $this->getMockBuilder(SmsDev::class)
            ->setMethods(['getGuzzleClient'])
            ->getMock();

        $stub->method('getGuzzleClient')->willReturn($client);

        return $stub;
    }

    public function getRequestPath()
    {
        return $this->_container[0]['request']->getUri()->getPath();
    }

    public function getRequestBody()
    {
        return \json_decode($this->_container[0]['request']->getBody()->getContents());
    }
}

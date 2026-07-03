<?php

namespace enricodias\SmsDev\Tests;

use enricodias\SmsDev;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

abstract class SmsDevMock extends TestCase
{
    protected $_container = [];

    public function getServiceMock($apiResponse = '', $apiKey = '')
    {
        \date_default_timezone_set('UTC');

        $this->_container = [];

        $mock = new MockHandler([
            new Response(200, [], $apiResponse),
        ]);

        $history = Middleware::history($this->_container);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);

        $httpFactory = new HttpFactory();

        return new SmsDev($apiKey, $client, $httpFactory, $httpFactory);
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

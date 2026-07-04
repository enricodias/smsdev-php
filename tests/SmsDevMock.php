<?php

namespace enricodias\SmsDev\Tests;

use enricodias\SmsDev\SmsDev;
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

    /**
     * @var TestLogger
     */
    protected $_logger;

    public function getServiceMock($apiResponse = '{}', $apiKey = '')
    {
        \date_default_timezone_set('UTC');

        $this->_container = [];
        $this->_logger = new TestLogger();

        $mock = new MockHandler([
            new Response(200, [], $apiResponse),
        ]);

        $history = Middleware::history($this->_container);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);

        $httpFactory = new HttpFactory();

        return new SmsDev($apiKey, $client, $httpFactory, $httpFactory, $this->_logger);
    }

    public function getRequestPath()
    {
        return $this->_container[0]['request']->getUri()->getPath();
    }

    public function getRequestBody()
    {
        return \json_decode($this->_container[0]['request']->getBody()->getContents());
    }

    public function getLogger(): TestLogger
    {
        return $this->_logger;
    }
}

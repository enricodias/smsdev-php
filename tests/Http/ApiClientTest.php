<?php

namespace enricodias\SmsDev\Tests;

use enricodias\SmsDev\Exceptions\InvalidResponseException;
use enricodias\SmsDev\Exceptions\TransportException;
use enricodias\SmsDev\Http\ApiClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Test sending requests and decoding responses in isolation.
 */
final class ApiClientTest extends TestCase
{
    public function testSendReturnsDecodedJsonBody()
    {
        $mock = new MockHandler([
            new Response(200, [], '{"situacao":"OK","saldo_sms":"1200"}'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $logger = new TestLogger();
        $apiClient = new ApiClient($client, $logger);

        $result = $apiClient->send($this->createRequest());

        $this->assertSame('OK', $result['situacao']);
        $this->assertSame('1200', $result['saldo_sms']);
        $this->assertSame($result, $apiClient->getLastResult());

        $this->assertTrue($logger->hasRecordWithContext('debug', 'Sending request to the SmsDev API.', [
            'method' => 'GET',
            'uri'    => 'https://api.smsdev.com.br/v1/balance',
            'body'   => '{}',
        ]));

        $this->assertTrue($logger->hasRecordWithContext('debug', 'Received response from the SmsDev API.', [
            'status' => 200,
            'body'   => '{"situacao":"OK","saldo_sms":"1200"}',
        ]));
    }

    public function testSendThrowsTransportExceptionOnClientFailure()
    {
        $mock = new MockHandler([
            new ConnectException('Connection failed', $this->createRequest()),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $logger = new TestLogger();
        $apiClient = new ApiClient($client, $logger);

        try {
            $apiClient->send($this->createRequest());

            $this->fail('Expected TransportException was not thrown.');
        } catch (TransportException $e) {
            $this->assertTrue($logger->hasRecordWithContext('error', 'Failed to send request to the SmsDev API.', [
                'method' => 'GET',
                'uri'    => 'https://api.smsdev.com.br/v1/balance',
                'reason' => 'Connection failed',
            ]));
        }
    }

    /**
     * @dataProvider invalidResponseBodyProvider
     */
    public function testSendThrowsInvalidResponseExceptionOnBadBody($body)
    {
        $mock = new MockHandler([
            new Response(200, [], $body),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $logger = new TestLogger();
        $apiClient = new ApiClient($client, $logger);

        try {
            $apiClient->send($this->createRequest());

            $this->fail('Expected InvalidResponseException was not thrown.');
        } catch (InvalidResponseException $e) {
            $this->assertTrue($logger->hasRecordWithContext('error', 'Invalid JSON response from the SmsDev API.', [
                'body' => $body,
            ]));
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function invalidResponseBodyProvider()
    {
        return [
            'empty body'   => [''],
            'invalid json' => ['{not json'],
            'json scalar'  => ['"just a string"'],
        ];
    }

    private function createRequest(): Request
    {
        $httpFactory = new HttpFactory();

        return new Request('GET', 'https://api.smsdev.com.br/v1/balance', [], $httpFactory->createStream('{}'));
    }
}

<?php

namespace enricodias\SmsDev\Tests;

use enricodias\SmsDev\Http\RequestBuilder;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;

/**
 * Test the PSR-7 request building in isolation.
 */
final class RequestBuilderTest extends TestCase
{
    public function testBuildSetsMethodUriAndHeader()
    {
        $httpFactory = new HttpFactory();

        $requestBuilder = new RequestBuilder($httpFactory, $httpFactory);

        $request = $requestBuilder->build('POST', 'https://api.smsdev.com.br/v1/send', [
            'key' => 'api_key',
        ]);

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://api.smsdev.com.br/v1/send', (string) $request->getUri());
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
    }

    public function testBuildEncodesParamsAsJsonBody()
    {
        $httpFactory = new HttpFactory();

        $requestBuilder = new RequestBuilder($httpFactory, $httpFactory);

        $request = $requestBuilder->build('GET', 'https://api.smsdev.com.br/v1/balance', [
            'key'    => 'api_key',
            'action' => 'saldo',
        ]);

        $body = \json_decode((string) $request->getBody());

        $this->assertSame('api_key', $body->key);
        $this->assertSame('saldo', $body->action);
    }
}

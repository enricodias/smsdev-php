<?php

namespace enricodias\SmsDev\Tests;

final class SmsDevTest extends SmsDevMock
{
    public function testApiKeyIsSent()
    {
        $this->getServiceMock('', 'api_key')->getBalance();

        $this->assertSame('api_key', $this->getRequestBody()->key);
    }
}

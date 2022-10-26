<?php

namespace enricodias\SmsDev\Tests;

final class SmsDevTest extends SmsDevMock
{
    public function testApiKeyWithConstructor()
    {
        $this->getServiceMock('', 'api_key')->getBalance();

        $this->assertSame('api_key', $this->getRequestBody()->key);
    }

    /**
     * @backupGlobals enabled
     */
    public function testApiKeyWithEnvVar()
    {
        $_SERVER['SMSDEV_API_KEY'] = 'api_key';

        $this->getServiceMock()->getBalance();

        $this->assertSame('api_key', $this->getRequestBody()->key);
    }

    /**
     * @testdox Api provided in the constructor has priority over the env var
     *
     * @backupGlobals enabled
     */
    public function testApiKeyWithConstructorAndEnvVar()
    {
        $_SERVER['SMSDEV_API_KEY'] = 'env_api_key';

        $this->getServiceMock('', 'api_key')->getBalance();

        $this->assertSame('api_key', $this->getRequestBody()->key);
    }
}

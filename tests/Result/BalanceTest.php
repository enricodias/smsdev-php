<?php

namespace enricodias\SmsDev\Tests\Result;

use enricodias\SmsDev\Result\Balance;
use PHPUnit\Framework\TestCase;

final class BalanceTest extends TestCase
{
    public function testJsonSerialize()
    {
        $balance = new Balance('OK', 1200, 'SALDO ATUAL');

        $expected = '{"situacao":"OK","saldo_sms":1200,"descricao":"SALDO ATUAL"}';

        $this->assertSame($expected, \json_encode($balance));
    }

    public function testFromArrayDefaultsMissingValuesToEmptyString()
    {
        $message = Balance::fromArray([
            'situacao' => 'OK',
        ]);

        $this->assertSame('OK', $message->getSituacao());
        $this->assertSame(0, $message->getSaldoSms());
        $this->assertSame('', $message->getDescricao());
    }
}

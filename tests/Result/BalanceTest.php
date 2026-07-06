<?php

namespace enricodias\SmsDev\Tests\Result;

use enricodias\SmsDev\Result\Balance;
use PHPUnit\Framework\TestCase;

final class BalanceTest extends TestCase
{
    /**
     * @dataProvider formattedBalanceProvider
     */
    public function testGetFormattedBalance(int $saldoSms, string $expected)
    {
        $balance = new Balance('OK', $saldoSms, 'SALDO ATUAL');

        $this->assertSame($expected, $balance->getFormattedBalance());
    }

    /**
     * @codeCoverageIgnore
     */
    public function formattedBalanceProvider()
    {
        return [
            'zero'        => [0, 'R$ 0,00'],
            'cents only'  => [5, 'R$ 0,05'],
            'whole reais' => [1200, 'R$ 12,00'],
            'thousands'   => [123456, 'R$ 1.234,56'],
        ];
    }

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
        $this->assertSame('R$ 0,00', $message->getFormattedBalance());
        $this->assertSame(0, $message->getSaldoSms());
        $this->assertSame('', $message->getDescricao());
    }
}

<?php

namespace enricodias\SmsDev\Tests\Callback;

use enricodias\SmsDev\Callback\CallbackParser;
use enricodias\SmsDev\Callback\MessageReceived;
use enricodias\SmsDev\Callback\StatusUpdate;
use PHPUnit\Framework\TestCase;

final class CallbackParserTest extends TestCase
{
    public function testParseReturnsStatusUpdateForDlrPayload()
    {
        $result = CallbackParser::parse([
            'key'         => 'XXXXXXXXXXXXXXX',
            'id'          => '123456789',
            'refer'       => 'XXXXXXX',
            'situacao'    => StatusUpdate::SITUACAO_RECEBIDA,
            'data_envio'  => '28022020145322',
            'operadora'   => 'VIVO-PORTABILIDADE',
            'qtd_credito' => '1',
        ]);

        $this->assertInstanceOf(StatusUpdate::class, $result);
    }

    /**
     * @dataProvider messageReceivedPayloadProvider
     */
    public function testParseReturnsMessageReceivedForMoPayload(array $payload)
    {
        $result = CallbackParser::parse($payload);

        $this->assertInstanceOf(MessageReceived::class, $result);
    }

    /**
     * @codeCoverageIgnore
     */
    public function messageReceivedPayloadProvider(): array
    {
        return [
            'full payload' => [[
                'from'    => '5562988887777',
                'id'      => '123456789',
                'id_sent' => '637849052',
                'message' => 'Teste de retorno',
                'refer'   => 'XXXXXXX',
            ]],
            'from only, no message' => [[
                'from' => '5562988887777',
            ]],
        ];
    }

    public function testParseThrowsForUnrecognizedPayload()
    {
        $this->expectException(\InvalidArgumentException::class);

        CallbackParser::parse([
            'foo' => 'bar',
        ]);
    }
}

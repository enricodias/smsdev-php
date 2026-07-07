<?php

namespace enricodias\SmsDev\Tests\Result;

use enricodias\SmsDev\Result\MessageResult;
use PHPUnit\Framework\TestCase;

final class MessageResultTest extends TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     */
    public function testJsonSerialize(array $data, string $expected)
    {
        $result = MessageResult::fromArray($data);

        $this->assertSame($expected, \json_encode($result));
    }

    /**
     * @codeCoverageIgnore
     */
    public function jsonSerializeDataProvider(): array
    {
        return [
            'send() shape' => [
                [
                    'situacao' => 'OK',
                    'codigo' => '1',
                    'id' => '637849052',
                    'descricao' => 'MENSAGEM NA FILA',
                    'refer' => 'Refer',
                ],
                '{"situacao":"OK","codigo":"1","id":"637849052","descricao":"MENSAGEM NA FILA"}',
            ],
            'cancel() shape' => [
                [
                    'situacao' => 'OK',
                    'codigo' => '1',
                    'id' => '9999999',
                    'descricao' => 'MENSAGEM CANCELADA COM SUCESSO',
                ],
                '{"situacao":"OK","codigo":"1","id":"9999999","descricao":"MENSAGEM CANCELADA COM SUCESSO"}',
            ],
        ];
    }

    public function testFromArrayDefaultsMissingValuesToEmptyString()
    {
        $result = MessageResult::fromArray([
            'situacao' => 'OK',
        ]);

        $this->assertSame('OK', $result->getSituacao());
        $this->assertSame('', $result->getCodigo());
        $this->assertSame('', $result->getId());
        $this->assertSame('', $result->getDescricao());
    }
}

<?php

namespace enricodias\SmsDev\Tests\Result;

use enricodias\SmsDev\Result\SendResult;
use PHPUnit\Framework\TestCase;

final class SendResultTest extends TestCase
{
    public function testJsonSerialize()
    {
        $result = SendResult::fromArray([
            'situacao' => 'OK',
            'codigo' => '1',
            'id' => '637849052',
            'descricao' => 'MENSAGEM NA FILA',
            'refer' => 'Refer',
        ]);

        $expected = '{"situacao":"OK","codigo":"1","id":"637849052","descricao":"MENSAGEM NA FILA"}';

        $this->assertSame($expected, \json_encode($result));
    }

    public function testFromArrayDefaultsMissingValuesToEmptyString()
    {
        $result = SendResult::fromArray([
            'situacao' => 'OK',
        ]);

        $this->assertSame('OK', $result->getSituacao());
        $this->assertSame('', $result->getCodigo());
        $this->assertSame('', $result->getId());
        $this->assertSame('', $result->getDescricao());
    }
}

<?php

namespace enricodias\SmsDev\Tests\Callback;

use enricodias\SmsDev\Callback\MessageReceived;
use PHPUnit\Framework\TestCase;

final class MessageReceivedTest extends TestCase
{
    public function testFromArray()
    {
        $message = MessageReceived::fromArray([
            'from'    => '5562988887777',
            'id'      => '123456789',
            'id_sent' => '637849052',
            'message' => 'Teste de retorno',
            'refer'   => 'XXXXXXX',
        ]);

        $this->assertSame('5562988887777', $message->getFrom());
        $this->assertSame('123456789', $message->getId());
        $this->assertSame('637849052', $message->getIdSent());
        $this->assertSame('Teste de retorno', $message->getMessage());
        $this->assertSame('XXXXXXX', $message->getRefer());
    }

    public function testFromArrayDefaultsMissingValuesToEmptyString()
    {
        $message = MessageReceived::fromArray([
            'from' => '5562988887777',
        ]);

        $this->assertSame('5562988887777', $message->getFrom());
        $this->assertSame('', $message->getId());
        $this->assertSame('', $message->getIdSent());
        $this->assertSame('', $message->getMessage());
        $this->assertSame('', $message->getRefer());
    }
}

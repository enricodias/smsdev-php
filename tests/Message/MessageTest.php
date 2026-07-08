<?php

namespace enricodias\SmsDev\Tests;

use enricodias\SmsDev\Message\Message;
use PHPUnit\Framework\TestCase;

final class MessageTest extends TestCase
{
    public function testCreate()
    {
        $message = Message::create('5511988887777', 'Message');

        $this->assertSame('5511988887777', $message->getNumber());
        $this->assertSame('Message', $message->getMessage());
        $this->assertNull($message->getRefer());
    }

    public function testSetRefer()
    {
        $message = Message::create('5511988887777', 'Message')->setRefer('Refer string');

        $this->assertSame('Refer string', $message->getRefer());
    }
}

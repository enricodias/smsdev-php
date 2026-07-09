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
        $this->assertNull($message->getSchedule());
    }

    public function testSetRefer()
    {
        $message = Message::create('5511988887777', 'Message')->setRefer('Refer string');

        $this->assertSame('Refer string', $message->getRefer());
    }

    public function testSetSchedule()
    {
        $schedule = new \DateTimeImmutable('2020-01-01 10:30:00');

        $message = Message::create('5511988887777', 'Message')->setSchedule($schedule);

        $this->assertSame($schedule, $message->getSchedule());
    }
}

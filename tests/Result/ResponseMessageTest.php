<?php

namespace enricodias\SmsDev\Tests\Result;

use enricodias\SmsDev\Result\ResponseMessage;
use PHPUnit\Framework\TestCase;

final class ResponseMessageTest extends TestCase
{
    public function testJsonSerialize()
    {
        $dataRead = new \DateTimeImmutable('2018-01-19T13:35:14+00:00');

        $message = ResponseMessage::fromArray($this->getResponse($dataRead));

        $expected = \json_encode($this->getResponse($dataRead->format(\DateTime::ATOM)));

        $this->assertSame($expected, \json_encode($message));
    }

    public function testJsonSerializeWithInvalidDataRead()
    {
        $message = ResponseMessage::fromArray($this->getResponse('Invalid Date'));

        $decoded = \json_decode(\json_encode($message), true);

        $this->assertSame('Invalid Date', $decoded['data_read']);
    }

    public function testFromArrayDefaultsMissingValuesToEmptyString()
    {
        $message = ResponseMessage::fromArray([
            'situacao' => 'OK',
        ]);

        $this->assertSame('OK', $message->getSituacao());
        $this->assertSame('', $message->getDataRead());
        $this->assertSame('', $message->getTelefone());
        $this->assertSame('', $message->getId());
        $this->assertSame('', $message->getRefer());
        $this->assertSame('', $message->getMsgSent());
        $this->assertSame('', $message->getIdSmsRead());
        $this->assertSame('', $message->getDescricao());
    }

    private function getResponse($dataRead): array
    {
        return [
            'situacao'    => 'OK',
            'data_read'   => $dataRead,
            'telefone'    => '5511988887777',
            'id'          => '2515974',
            'refer'       => 'Refer',
            'msg_sent'    => 'Message sent',
            'id_sms_read' => 'id sms read',
            'descricao'   => 'Resposta',
        ];
    }
}

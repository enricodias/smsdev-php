<?php

namespace enricodias\SmsDev\Tests\Result;

use enricodias\SmsDev\Result\StatusResult;
use PHPUnit\Framework\TestCase;

final class StatusResultTest extends TestCase
{
    public function testJsonSerialize()
    {
        $dataEnvio = new \DateTimeImmutable('2019-10-21T11:08:58+00:00');

        $status = StatusResult::fromArray($this->getResponse($dataEnvio));

        $expected = \json_encode($this->getResponse($dataEnvio->format(\DateTime::ATOM)));

        $this->assertSame($expected, \json_encode($status));
    }

    public function testJsonSerializeWithNullDataEnvio()
    {
        $status = StatusResult::fromArray($this->getResponse(null));

        $decoded = \json_decode(\json_encode($status), true);

        $this->assertNull($decoded['data_envio']);
    }

    public function testFromArrayDefaultsMissingValuesToEmptyString()
    {
        $status = StatusResult::fromArray([
            'situacao' => 'OK',
        ]);

        $this->assertSame('OK', $status->getSituacao());
        $this->assertSame('', $status->getCodigo());
        $this->assertNull($status->getDataEnvio());
        $this->assertSame('', $status->getOperadora());
        $this->assertSame('', $status->getDescricao());
    }

    /**
     * @param \DateTimeInterface|string|null $dataEnvio
     */
    private function getResponse($dataEnvio): array
    {
        return [
            'situacao'   => 'OK',
            'codigo'     => '1',
            'data_envio' => $dataEnvio,
            'operadora'  => 'OI',
            'descricao'  => 'RECEBIDA',
        ];
    }
}

<?php

namespace enricodias\SmsDev\Tests\Callback;

use enricodias\SmsDev\Callback\StatusUpdate;
use PHPUnit\Framework\TestCase;

final class StatusUpdateTest extends TestCase
{
    public function testFromArrayParsesDataEnvioWithoutSeparators()
    {
        \date_default_timezone_set('UTC');

        $status = StatusUpdate::fromArray($this->getPayload('28022020145322'));

        $this->assertSame('123456789', $status->getId());
        $this->assertSame('XXXXXXX', $status->getRefer());
        $this->assertSame(StatusUpdate::SITUACAO_RECEBIDA, $status->getSituacao());
        $this->assertSame('VIVO-PORTABILIDADE', $status->getOperadora());
        $this->assertSame('1', $status->getQtdCredito());

        $dataEnvio = $status->getDataEnvio();

        $this->assertInstanceOf(\DateTimeInterface::class, $dataEnvio);
        // 28/02/2020 14:53:22 in America/Sao_Paulo (UTC-3) is 17:53:22 in UTC.
        $this->assertSame('28-02-2020 17:53:22', $dataEnvio->format('d-m-Y H:i:s'));
    }

    public function testFromArrayWithUnparseableDataEnvioReturnsNull()
    {
        $status = StatusUpdate::fromArray($this->getPayload('not-a-date'));

        $this->assertNull($status->getDataEnvio());
    }

    public function testFromArrayDefaultsMissingValuesToEmptyString()
    {
        $status = StatusUpdate::fromArray([
            'situacao' => StatusUpdate::SITUACAO_ENVIADA,
        ]);

        $this->assertSame('', $status->getId());
        $this->assertSame('', $status->getRefer());
        $this->assertSame(StatusUpdate::SITUACAO_ENVIADA, $status->getSituacao());
        $this->assertNull($status->getDataEnvio());
        $this->assertSame('', $status->getOperadora());
        $this->assertSame('', $status->getQtdCredito());
    }

    /**
     * @dataProvider situacaoProvider
     */
    public function testSituacaoClassificationMethods(
        string $situacao,
        bool $expectedIsDelivered,
        bool $expectedIsSent,
        bool $expectedIsPending,
        bool $expectedIsRejected
    ) {
        $status = StatusUpdate::fromArray([
            'situacao' => $situacao,
        ]);

        $this->assertSame($expectedIsDelivered, $status->isDelivered());
        $this->assertSame($expectedIsSent, $status->isSent());
        $this->assertSame($expectedIsPending, $status->isPending());
        $this->assertSame($expectedIsRejected, $status->isRejected());
    }

    public function situacaoProvider(): array
    {
        return [
            'received'   => [StatusUpdate::SITUACAO_RECEBIDA, true, true, false, false],
            'sent'       => [StatusUpdate::SITUACAO_ENVIADA, false, true, false, false],
            'error'      => [StatusUpdate::SITUACAO_ERRO, false, false, false, true],
            'queued'     => [StatusUpdate::SITUACAO_FILA, false, false, true, false],
            'cancelled'  => [StatusUpdate::SITUACAO_CANCELADA, false, false, false, true],
            'black list' => [StatusUpdate::SITUACAO_BLACK_LIST, false, false, false, true],
            'approval'   => [StatusUpdate::SITUACAO_APROVACAO, false, false, true, false],
            'unknown'    => ['UNKNOWN', false, false, false, false],
        ];
    }

    private function getPayload(string $dataEnvio): array
    {
        return [
            'key'         => 'XXXXXXXXXXXXXXX',
            'id'          => '123456789',
            'refer'       => 'XXXXXXX',
            'situacao'    => StatusUpdate::SITUACAO_RECEBIDA,
            'data_envio'  => $dataEnvio,
            'operadora'   => 'VIVO-PORTABILIDADE',
            'qtd_credito' => '1',
        ];
    }
}

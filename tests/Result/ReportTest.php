<?php

namespace enricodias\SmsDev\Tests\Result;

use enricodias\SmsDev\Result\Report;
use PHPUnit\Framework\TestCase;

final class ReportTest extends TestCase
{
    public function testJsonSerialize()
    {
        $dataInicio = new \DateTimeImmutable('2020-01-01T00:00:00+00:00');
        $dataFim    = new \DateTimeImmutable('2020-01-30T00:00:00+00:00');

        $report = Report::fromArray($this->getResponse($dataInicio, $dataFim));

        $expected = \json_encode($this->getResponse(
            $dataInicio->format(\DateTime::ATOM),
            $dataFim->format(\DateTime::ATOM)
        ));

        $this->assertSame($expected, \json_encode($report));
    }

    public function testJsonSerializeWithInvalidDates()
    {
        $report = Report::fromArray($this->getResponse('Invalid Date', 'Invalid Date'));

        $decoded = \json_decode(\json_encode($report), true);

        $this->assertSame('Invalid Date', $decoded['data_inicio']);
        $this->assertSame('Invalid Date', $decoded['data_fim']);
    }

    public function testGetters()
    {
        $dataInicio = new \DateTimeImmutable('2020-01-01T00:00:00+00:00');
        $dataFim    = new \DateTimeImmutable('2020-01-30T00:00:00+00:00');

        $report = Report::fromArray($this->getResponse($dataInicio, $dataFim));

        $this->assertTrue($report->isSuccess());
        $this->assertSame('OK', $report->getSituacao());
        $this->assertSame('1', $report->getCodigo());
        $this->assertSame($dataInicio, $report->getDataInicio());
        $this->assertSame($dataFim, $report->getDataFim());
        $this->assertSame(100, $report->getEnviada());
        $this->assertSame(10200, $report->getRecebida());
        $this->assertSame(0, $report->getBlacklist());
        $this->assertSame(0, $report->getCancelada());
        $this->assertSame(10300, $report->getQtdCredito());
        $this->assertSame('CONSULTA REALIZADA', $report->getDescricao());
    }

    public function testFromArrayDefaultsMissingValuesToEmptyOrZero()
    {
        $report = Report::fromArray([
            'situacao' => 'ERRO',
        ]);

        $this->assertFalse($report->isSuccess());
        $this->assertSame('ERRO', $report->getSituacao());
        $this->assertSame('', $report->getCodigo());
        $this->assertSame('', $report->getDataInicio());
        $this->assertSame('', $report->getDataFim());
        $this->assertSame(0, $report->getEnviada());
        $this->assertSame(0, $report->getRecebida());
        $this->assertSame(0, $report->getBlacklist());
        $this->assertSame(0, $report->getCancelada());
        $this->assertSame(0, $report->getQtdCredito());
        $this->assertSame('', $report->getDescricao());
    }

    /**
     * @param \DateTimeInterface|string $dataInicio
     * @param \DateTimeInterface|string $dataFim
     */
    private function getResponse($dataInicio, $dataFim): array
    {
        return [
            'situacao'    => 'OK',
            'codigo'      => '1',
            'data_inicio' => $dataInicio,
            'data_fim'    => $dataFim,
            'enviada'     => 100,
            'recebida'    => 10200,
            'blacklist'   => 0,
            'cancelada'   => 0,
            'qtd_credito' => 10300,
            'descricao'   => 'CONSULTA REALIZADA',
        ];
    }
}

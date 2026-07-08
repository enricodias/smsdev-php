<?php

namespace enricodias\SmsDev\Result;

/**
 * The getReport() (Total Report) response.
 */
class Report extends AbstractResult
{
    /**
     * Error code table missing from official docs. Assuming "1" for success.
     *
     * @var string
     */
    private $codigo;

    /**
     * Start date of the requested period, converted to the local timezone.
     *
     * @var \DateTimeInterface|null
     */
    private $dataInicio;

    /**
     * End date of the requested period, converted to the local timezone.
     *
     * @var \DateTimeInterface|null
     */
    private $dataFim;

    /**
     * Number of messages sent in the period.
     *
     * @var int
     */
    private $enviada;

    /**
     * Number of messages received in the period.
     *
     * @var int
     */
    private $recebida;

    /**
     * Number of messages blocked by the blacklist in the period.
     *
     * @var int
     */
    private $blacklist;

    /**
     * Number of messages cancelled in the period.
     *
     * @var int
     */
    private $cancelada;

    /**
     * Credits consumed in the period.
     *
     * @var int
     */
    private $qtdCredito;

    /**
     * @var string
     */
    private $descricao;

    public function __construct(
        string $situacao,
        string $codigo,
        ?\DateTimeInterface $dataInicio,
        ?\DateTimeInterface $dataFim,
        int $enviada,
        int $recebida,
        int $blacklist,
        int $cancelada,
        int $qtdCredito,
        string $descricao
    ) {
        $this->situacao = $situacao;
        $this->codigo = $codigo;
        $this->dataInicio = $dataInicio;
        $this->dataFim = $dataFim;
        $this->enviada = $enviada;
        $this->recebida = $recebida;
        $this->blacklist = $blacklist;
        $this->cancelada = $cancelada;
        $this->qtdCredito = $qtdCredito;
        $this->descricao = $descricao;
    }

    /**
     * Builds a Report from a decoded API response.
     */
    public static function fromArray(array $data): self
    {
        $defaults = [
            'situacao'    => '',
            'codigo'      => '',
            'data_inicio' => null,
            'data_fim'    => null,
            'enviada'     => 0,
            'recebida'    => 0,
            'blacklist'   => 0,
            'cancelada'   => 0,
            'qtd_credito' => 0,
            'descricao'   => '',
        ];

        $data = \array_merge($defaults, $data);

        return new self(...\array_values($data));
    }

    /**
     * Error code table missing from official docs. Assuming "1" for success.
     */
    public function getCodigo(): string
    {
        return $this->codigo;
    }

    /**
     * Start date of the requested period, converted to the local timezone.
     */
    public function getDataInicio(): ?\DateTimeInterface
    {
        return $this->dataInicio;
    }

    /**
     * End date of the requested period, converted to the local timezone.
     */
    public function getDataFim(): ?\DateTimeInterface
    {
        return $this->dataFim;
    }

    /**
     * Number of messages sent in the period.
     */
    public function getEnviada(): int
    {
        return $this->enviada;
    }

    /**
     * Number of messages received in the period.
     */
    public function getRecebida(): int
    {
        return $this->recebida;
    }

    /**
     * Number of messages blocked by the blacklist in the period.
     */
    public function getBlacklist(): int
    {
        return $this->blacklist;
    }

    /**
     * Number of messages cancelled in the period.
     */
    public function getCancelada(): int
    {
        return $this->cancelada;
    }

    /**
     * Credits consumed in the period.
     */
    public function getQtdCredito(): int
    {
        return $this->qtdCredito;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    /**
     * The dates are serialized in ISO 8601 format, converted to the local timezone.
     */
    public function jsonSerialize(): array
    {
        return [
            ...parent::jsonSerialize(),
            'codigo'      => $this->codigo,
            'data_inicio' => $this->dataInicio !== null ? $this->dataInicio->format(\DateTime::ATOM) : null,
            'data_fim'    => $this->dataFim !== null ? $this->dataFim->format(\DateTime::ATOM) : null,
            'enviada'     => $this->enviada,
            'recebida'    => $this->recebida,
            'blacklist'   => $this->blacklist,
            'cancelada'   => $this->cancelada,
            'qtd_credito' => $this->qtdCredito,
            'descricao'   => $this->descricao,
        ];
    }
}

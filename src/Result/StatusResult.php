<?php

namespace enricodias\SmsDev\Result;

/**
 * Status Inquiry (DLR) response.
 */
class StatusResult extends AbstractResult
{
    /**
     * Error code table missing from official docs. Assuming "1" for success.
     *
     * @var string
     */
    private $codigo;

    /**
     * Date the message was sent, converted to the local timezone.
     *
     * @var \DateTimeInterface|null
     */
    private $dataEnvio;

    /**
     * Carrier of the recipient phone.
     *
     * @var string
     */
    private $operadora;

    /**
     * Delivery status of the message: RECEBIDA, ENVIADA, FILA, CANCELADA, BLACK LIST,
     * APROVACAO or ERRO.
     *
     * @var string
     */
    private $descricao;

    public function __construct(
        string $situacao,
        string $codigo,
        ?\DateTimeInterface $dataEnvio,
        string $operadora,
        string $descricao
    ) {
        $this->situacao = $situacao;
        $this->codigo = $codigo;
        $this->dataEnvio = $dataEnvio;
        $this->operadora = $operadora;
        $this->descricao = $descricao;
    }

    /**
     * Builds a StatusResult from a decoded API response.
     */
    public static function fromArray(array $data): self
    {
        $defaults = [
            'situacao'   => '',
            'codigo'     => '',
            'data_envio' => null,
            'operadora'  => '',
            'descricao'  => '',
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
     * Date the message was sent, converted to the local timezone.
     *
     * The API always reports dates in the America/Sao_Paulo timezone. This value is
     * already converted to \date_default_timezone_get(), use \DateTimeInterface::format()
     * to render it in whatever format is needed.
     */
    public function getDataEnvio(): ?\DateTimeInterface
    {
        return $this->dataEnvio;
    }

    /**
     * Carrier of the recipient phone.
     */
    public function getOperadora(): string
    {
        return $this->operadora;
    }

    /**
     * Delivery status of the message: RECEBIDA, ENVIADA, FILA, CANCELADA, BLACK LIST,
     * APROVACAO or ERRO.
     */
    public function getDescricao(): string
    {
        return $this->descricao;
    }

    /**
     * The date is serialized in ISO 8601 format, converted to the local timezone.
     */
    public function jsonSerialize(): array
    {
        return [
            ...parent::jsonSerialize(),
            'codigo'     => $this->codigo,
            'data_envio' => $this->dataEnvio !== null ? $this->dataEnvio->format(\DateTime::ATOM) : null,
            'operadora'  => $this->operadora,
            'descricao'  => $this->descricao,
        ];
    }
}

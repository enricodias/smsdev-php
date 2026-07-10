<?php

namespace enricodias\SmsDev\Callback;

use enricodias\SmsDev\DateTime\ApiDateConverter;

/**
 * Inbound Callback Situacao (DLR) payload.
 *
 * SmsDev sends this to the URL configured in the account panel whenever the delivery
 * status of a previously sent message changes. Build it with CallbackParser::parse() from
 * the decoded body of that inbound request, this class does not make any outbound API call.
 */
class StatusUpdate
{
    public const SITUACAO_RECEBIDA = 'RECEBIDA';
    public const SITUACAO_ENVIADA = 'ENVIADA';
    public const SITUACAO_ERRO = 'ERRO';
    public const SITUACAO_FILA = 'FILA';
    public const SITUACAO_CANCELADA = 'CANCELADA';
    public const SITUACAO_BLACK_LIST = 'BLACK LIST';
    public const SITUACAO_APROVACAO = 'APROVACAO';

    /**
     * Format used by the data_envio field on this callback: ddmmyyyyHHiiss, with no
     * separators, unlike the d/m/Y H:i:s format used everywhere else in the API.
     *
     * @var string
     */
    private const DATA_ENVIO_FORMAT = 'dmYHis';

    /**
     * Unique ID of the message that was sent (MT).
     *
     * @var string
     */
    private $id;

    /**
     * Refer identifier passed when the original message was sent (MT).
     *
     * @var string
     */
    private $refer;

    /**
     * Delivery status of the message. One of the SITUACAO_* constants.
     *
     * @var string
     */
    private $situacao;

    /**
     * Date the message was sent, converted to the local timezone.
     *
     * @var \DateTimeInterface|null
     */
    private $dataEnvio;

    /**
     * Carrier identified for the recipient phone (HLR).
     *
     * @var string
     */
    private $operadora;

    /**
     * Amount of credit consumed.
     *
     * @var string
     */
    private $qtdCredito;

    public function __construct(
        string $id,
        string $refer,
        string $situacao,
        ?\DateTimeInterface $dataEnvio,
        string $operadora,
        string $qtdCredito
    ) {
        $this->id = $id;
        $this->refer = $refer;
        $this->situacao = $situacao;
        $this->dataEnvio = $dataEnvio;
        $this->operadora = $operadora;
        $this->qtdCredito = $qtdCredito;
    }

    /**
     * Builds a StatusUpdate from the decoded body of an inbound callback request.
     */
    public static function fromArray(array $data): self
    {
        $defaults = [
            'id'          => '',
            'refer'       => '',
            'situacao'    => '',
            'data_envio'  => null,
            'operadora'   => '',
            'qtd_credito' => '',
        ];

        if (\array_key_exists('data_envio', $data) && \is_string($data['data_envio'])) {
            $data['data_envio'] = ApiDateConverter::fromApiFormat($data['data_envio'], self::DATA_ENVIO_FORMAT);
        }

        $data = \array_merge($defaults, $data);

        return new self(...\array_values($data));
    }

    /**
     * Message was delivered to the recipient's phone.
     */
    public function isDelivered(): bool
    {
        return $this->situacao === self::SITUACAO_RECEBIDA;
    }

    /**
     * Message was sent to the recipient's phone.
     */
    public function isSent(): bool
    {
        return $this->situacao === self::SITUACAO_RECEBIDA ||
               $this->situacao === self::SITUACAO_ENVIADA;
    }

    /**
     * Message is in queue
     */
    public function isPending(): bool
    {
        return $this->situacao === self::SITUACAO_FILA ||
               $this->situacao === self::SITUACAO_APROVACAO;
    }

    /**
     * Message was rejected.
     */
    public function isRejected(): bool
    {
        return $this->situacao === self::SITUACAO_ERRO ||
               $this->situacao === self::SITUACAO_CANCELADA ||
               $this->situacao === self::SITUACAO_BLACK_LIST;
    }

    /**
     * Unique ID of the message that was sent (MT).
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Refer identifier passed when the original message was sent (MT).
     */
    public function getRefer(): string
    {
        return $this->refer;
    }

    /**
     * Delivery status of the message. One of the SITUACAO_* constants.
     */
    public function getSituacao(): string
    {
        return $this->situacao;
    }

    /**
     * Date the message was sent, converted to the local timezone.
     */
    public function getDataEnvio(): ?\DateTimeInterface
    {
        return $this->dataEnvio;
    }

    /**
     * Carrier identified for the recipient phone (HLR).
     */
    public function getOperadora(): string
    {
        return $this->operadora;
    }

    /**
     * Amount of credit consumed.
     */
    public function getQtdCredito(): string
    {
        return $this->qtdCredito;
    }
}

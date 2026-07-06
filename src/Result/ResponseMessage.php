<?php

namespace enricodias\SmsDev\Result;

/**
 * One item of a fetch() response.
 */
class ResponseMessage implements \JsonSerializable
{
    /**
     * "OK" - Successful submission
     * "ERROR" - Submission with error
     *
     * @var string
     */
    private $situacao;

    /**
     * Date the message was received, converted to the local timezone.
     *
     * @var \DateTimeInterface|string
     */
    private $dataRead;

    /**
     * Phone that sent the answer.
     *
     * @var string
     */
    private $telefone;

    /**
     * Unique ID of the sending message (MT).
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
     * Text of the received message (MO).
     *
     * @var string
     */
    private $msgSent;

    /**
     * Unique ID of the received message (MO).
     *
     * @var string
     */
    private $idSmsRead;

    /**
     * @var string|null
     */
    private $descricao;

    public function __construct(
        string $situacao,
        $dataRead,
        string $telefone,
        string $id,
        string $refer,
        string $msgSent,
        string $idSmsRead,
        string $descricao
    ) {
        $this->situacao = $situacao;
        $this->dataRead = $dataRead;
        $this->telefone = $telefone;
        $this->id = $id;
        $this->refer = $refer;
        $this->msgSent = $msgSent;
        $this->idSmsRead = $idSmsRead;
        $this->descricao = $descricao;
    }

    /**
     * Builds a ResponseMessage from a decoded API response item.
     *
     * @param array $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            \array_key_exists('situacao', $data) ? $data['situacao'] : '',
            \array_key_exists('data_read', $data) ? $data['data_read'] : '',
            \array_key_exists('telefone', $data) ? $data['telefone'] : '',
            \array_key_exists('id', $data) ? $data['id'] : '',
            \array_key_exists('refer', $data) ? $data['refer'] : '',
            \array_key_exists('msg_sent', $data) ? $data['msg_sent'] : '',
            \array_key_exists('id_sms_read', $data) ? $data['id_sms_read'] : '',
            \array_key_exists('descricao', $data) ? $data['descricao'] : ''
        );
    }

    /**
     * Whether this item is an actual received message (field "situacao" equals "OK").
     */
    public function isSuccess(): bool
    {
        return $this->situacao === 'OK';
    }

    /**
     * "OK" - Successful submission
     * "ERROR" - Submission with error
     */
    public function getSituacao(): string
    {
        return $this->situacao;
    }

    /**
     * Date the message was received, converted to the local timezone.
     *
     * The API always reports dates in the America/Sao_Paulo timezone. This value is
     * already converted to \date_default_timezone_get(), use \DateTimeInterface::format()
     * to render it in whatever format is needed.
     *
     * @return \DateTimeInterface|string fallback to string if the api doesn't send a valid date.
     */
    public function getDataRead()
    {
        return $this->dataRead;
    }

    /**
     * Phone that sent the answer.
     */
    public function getTelefone(): string
    {
        return $this->telefone;
    }

    /**
     * Unique ID of the sending message (MT).
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Refer identifier passed when the original message was sent (MT).
     */
    public function getRefer(): ?string
    {
        return $this->refer;
    }

    public function getMsgSent(): ?string
    {
        return $this->msgSent;
    }

    /**
     * Unique ID of the received message (MO).
     */
    public function getIdSmsRead(): string
    {
        return $this->idSmsRead;
    }

    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    /**
     * The date is serialized in ISO 8601 format, converted to the local timezone.
     */
    public function jsonSerialize(): array
    {
        return [
            'situacao'    => $this->situacao,
            'data_read'   => $this->dataRead instanceof \DateTimeInterface ? $this->dataRead->format(\DateTime::ATOM) : $this->dataRead,
            'telefone'    => $this->telefone,
            'id'          => $this->id,
            'refer'       => $this->refer,
            'msg_sent'    => $this->msgSent,
            'id_sms_read' => $this->idSmsRead,
            'descricao'   => $this->descricao,
        ];
    }
}

<?php

namespace enricodias\SmsDev\Result;

/**
 * One item of a send() response.
 */
class SendResult implements \JsonSerializable
{
    /**
     * "OK" - Successful submission
     * "ERROR" - Submission with error
     *
     * @var string
     */
    private $situacao;

    /**
     * Error code table missing from official docs. Assuming "1" for success.
     *
     * @var string
     */
    private $codigo;

    /**
     * Unique ID of the sent message.
     *
     * @var string
     */
    private $id;

    /**
     * Description of the operation or error.
     *
     * @var string
     */
    private $descricao;

    public function __construct(
        string $situacao,
        string $codigo,
        string $id,
        string $descricao
    ) {
        $this->situacao = $situacao;
        $this->codigo = $codigo;
        $this->id = $id;
        $this->descricao = $descricao;
    }

    /**
     * Builds a SendResult from a decoded API response item.
     *
     * @param array $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            \array_key_exists('situacao', $data) ? $data['situacao'] : '',
            \array_key_exists('codigo', $data) ? $data['codigo'] : '',
            \array_key_exists('id', $data) ? $data['id'] : '',
            \array_key_exists('descricao', $data) ? $data['descricao'] : ''
        );
    }

    /**
     * Whether this item was accepted by the API (field "situacao" equals "OK").
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
     * Error code table missing from official docs. Assuming "1" for success.
     */
    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    /**
     * Unique ID of the sent message.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Description of the operation or error.
     */
    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function jsonSerialize(): array
    {
        return [
            'situacao'  => $this->situacao,
            'codigo'    => $this->codigo,
            'id'        => $this->id,
            'descricao' => $this->descricao
        ];
    }
}

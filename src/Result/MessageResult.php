<?php

namespace enricodias\SmsDev\Result;

/**
 * One item of a send() or cancel() response.
 *
 * Both endpoints are grouped by the API as MT (Mobile Terminated) operations and share
 * the exact same response shape.
 */
class MessageResult extends AbstractResult
{
    /**
     * Error code table missing from official docs. Assuming "1" for success.
     *
     * @var string
     */
    private $codigo;

    /**
     * Unique ID of the message.
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
     * Builds a MessageResult from a decoded API response item.
     */
    public static function fromArray(array $data): self
    {
        $defaults = [
            'situacao'  => '',
            'codigo'    => '',
            'id'        => '',
            'descricao' => '',
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
     * Unique ID of the message.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Description of the operation or error.
     */
    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function jsonSerialize(): array
    {
        return [
            ...parent::jsonSerialize(),
            'codigo'    => $this->codigo,
            'id'        => $this->id,
            'descricao' => $this->descricao
        ];
    }
}

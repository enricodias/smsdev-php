<?php

namespace enricodias\SmsDev\Result;

abstract class AbstractResult implements \JsonSerializable
{
    public const API_SUCCESS = "OK";
    public const API_ERROR = "ERRO";

    /**
     * @var string
     */
    protected $situacao;

    /**
     * Whether the query itself succeeded.
     *
     * This reflects whether the status could be retrieved, not the delivery status of the
     * message itself. Use getDescricao() for the actual delivery status.
     */
    public function isSuccess(): bool
    {
        return $this->situacao === self::API_SUCCESS;
    }

    /**
     * "OK" - The query itself succeeded
     * "ERRO" - The query itself failed (e.g. invalid id)
     */
    public function getSituacao(): string
    {
        return $this->situacao;
    }

    public function jsonSerialize(): array
    {
        return [
            'situacao' => $this->situacao,
        ];
    }
}

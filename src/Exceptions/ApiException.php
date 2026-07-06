<?php

namespace enricodias\SmsDev\Exceptions;

/**
 * Thrown when the API itself reports a failure (situacao: "ERRO") for an endpoint
 * that does not support partial/per-item results.
 */
class ApiException extends SmsDevException
{
    /**
     * @var string
     */
    private $apiCode;

    /**
     * @var string
     */
    private $description;

    public function __construct(string $apiCode, string $description)
    {
        $this->apiCode = $apiCode;
        $this->description = $description;

        parent::__construct(\sprintf('SmsDev API error %s: %s', $apiCode, $description));
    }

    /**
     * API error code (field "codigo" in the API response).
     *
     * Some endpoints don't return a code.
     */
    public function getApiCode(): string
    {
        return $this->apiCode;
    }

    /**
     * Human readable error message (field "descricao" in the API response).
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}

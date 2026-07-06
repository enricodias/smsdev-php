<?php

namespace enricodias\SmsDev\Result;

/**
 * The getBalance() response.
 */
class Balance implements \JsonSerializable
{
    /**
     * "OK" - Successful submission
     * "ERROR" - Submission with error
     *
     * @var string
     */
    private $situacao;

    /**
     * Current balance in BRL cents.
     *
     * @var int
     */
    private $saldoSms;

    /**
     * @var string
     */
    private $descricao;

    public function __construct(
        string $situacao,
        int $saldoSms,
        string $descricao
    ) {
        $this->situacao = $situacao;
        $this->saldoSms = $saldoSms;
        $this->descricao = $descricao;
    }

    /**
     * Builds a Balance from a decoded API response.
     *
     * @param array $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            \array_key_exists('situacao', $data) ? $data['situacao'] : '',
            \array_key_exists('saldo_sms', $data) ? (int) $data['saldo_sms'] : 0,
            \array_key_exists('descricao', $data) ? $data['descricao'] : '',
        );
    }

    /**
     * Whether the request was accepted by the API (field "situacao" equals "OK").
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
     * Current balance in BRL cents.
     */
    public function getSaldoSms(): int
    {
        return $this->saldoSms;
    }

    /**
     * Current balance formatted as Brazilian Real (ex: "R$ 1,23").
     */
    public function getFormattedBalance(): string
    {
        $reais = $this->saldoSms / 100;

        return 'R$ '.\number_format($reais, 2, ',', '.');
    }

    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function jsonSerialize(): array
    {
        return [
            'situacao' => $this->situacao,
            'saldo_sms' => $this->saldoSms,
            'descricao' => $this->descricao,
        ];
    }
}

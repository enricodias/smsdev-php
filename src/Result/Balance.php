<?php

namespace enricodias\SmsDev\Result;

/**
 * The getBalance() response.
 */
class Balance  extends AbstractResult
{
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
     */
    public static function fromArray(array $data): self
    {
        $defaults = [
            'situacao'  => '',
            'saldo_sms' => 0,
            'descricao' => '',
        ];

        $data = \array_merge($defaults, $data);

        return new self(...\array_values($data));
    }

    /**
     * Current balance in credits.
     *
     * Sending one sms consumes 1 credit.
     */
    public function getSaldoSms(): int
    {
        return $this->saldoSms;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function jsonSerialize(): array
    {
        return [
            ...parent::jsonSerialize(),
            'saldo_sms' => $this->saldoSms,
            'descricao' => $this->descricao,
        ];
    }
}

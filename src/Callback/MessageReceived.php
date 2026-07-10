<?php

namespace enricodias\SmsDev\Callback;

/**
 * Inbound Callback Retorno (MO) payload.
 *
 * SmsDev sends this to the URL configured in the account panel whenever a reply to a
 * previously sent message is received. Build it with CallbackParser::parse() from the
 * decoded body of that inbound request, this class does not make any outbound API call.
 */
class MessageReceived
{
    /**
     * Phone number that sent the reply.
     *
     * @var string
     */
    private $from;

    /**
     * Unique ID of this reply (MO).
     *
     * @var string
     */
    private $id;

    /**
     * Unique ID of the original message that was sent (MT).
     *
     * @var string
     */
    private $idSent;

    /**
     * Text of the reply.
     *
     * @var string
     */
    private $message;

    /**
     * Refer identifier passed when the original message was sent (MT).
     *
     * @var string
     */
    private $refer;

    public function __construct(
        string $from,
        string $id,
        string $idSent,
        string $message,
        string $refer
    ) {
        $this->from = $from;
        $this->id = $id;
        $this->idSent = $idSent;
        $this->message = $message;
        $this->refer = $refer;
    }

    /**
     * Builds a MessageReceived from the decoded body of an inbound callback request.
     */
    public static function fromArray(array $data): self
    {
        $defaults = [
            'from'    => '',
            'id'      => '',
            'id_sent' => '',
            'message' => '',
            'refer'   => '',
        ];

        $data = \array_merge($defaults, $data);

        return new self(...\array_values($data));
    }

    /**
     * Phone number that sent the reply.
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * Unique ID of this reply (MO).
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Unique ID of the original message that was sent (MT).
     */
    public function getIdSent(): string
    {
        return $this->idSent;
    }

    /**
     * Text of the reply.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Refer identifier passed when the original message was sent (MT).
     */
    public function getRefer(): string
    {
        return $this->refer;
    }
}

<?php

namespace enricodias\SmsDev\Message;

/**
 * A single message to be sent through SmsDev::send() or SmsDev::sendMultiple().
 */
class Message
{
    /**
     * @var string
     */
    private $number;

    /**
     * @var string
     */
    private $message;

    /**
     * User reference for message identification.
     *
     * @var string|null
     */
    private $refer;

    private function __construct(string $number, string $message)
    {
        $this->number = $number;
        $this->message = $message;
    }

    /**
     * Creates a new Message.
     */
    public static function create(string $number, string $message): self
    {
        return new self($number, $message);
    }

    /**
     * Sets a user reference for this message.
     */
    public function setRefer(string $refer): self
    {
        $this->refer = $refer;

        return $this;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * User reference for message identification.
     */
    public function getRefer(): ?string
    {
        return $this->refer;
    }
}

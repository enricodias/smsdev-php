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

    /**
     * Date and time this message should be sent at, instead of immediately.
     *
     * @var \DateTimeInterface|null
     */
    private $schedule;

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

    /**
     * Schedules this message to be sent at a later date and time, instead of immediately.
     */
    public function setSchedule(\DateTimeInterface $schedule): self
    {
        $this->schedule = $schedule;

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

    /**
     * Date and time this message should be sent at, instead of immediately.
     */
    public function getSchedule(): ?\DateTimeInterface
    {
        return $this->schedule;
    }
}

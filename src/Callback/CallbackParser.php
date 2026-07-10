<?php

namespace enricodias\SmsDev\Callback;

/**
 * Parses the body of an inbound SmsDev callback request into a typed object.
 *
 * SmsDev calls a URL configured in the account panel for two unrelated events: a reply to
 * a sent message (Callback Retorno / MO) and a delivery status change (Callback Situacao /
 * DLR). Both payloads are posted to the same configured URL, so parse() inspects the
 * decoded body and returns the matching type.
 *
 * @see https://www.smsdev.com.br/callback-recebimento-sms/
 * @see https://www.smsdev.com.br/callback-situacao-da-mensagem-dlr/
 */
class CallbackParser
{
    /**
     * Parses the decoded body of an inbound callback request.
     *
     * @param array $data Decoded JSON body, or the equivalent POST/GET params.
     *
     * @return MessageReceived|StatusUpdate
     *
     * @throws \InvalidArgumentException If the payload matches neither known callback shape.
     */
    public static function parse(array $data)
    {
        if (\array_key_exists('situacao', $data)) {
            return StatusUpdate::fromArray($data);
        }

        if (\array_key_exists('message', $data) || \array_key_exists('from', $data)) {
            return MessageReceived::fromArray($data);
        }

        throw new \InvalidArgumentException('Unable to determine the callback type from the given payload.');
    }
}

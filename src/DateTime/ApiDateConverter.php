<?php

namespace enricodias\SmsDev\DateTime;

/**
 * Converts dates between the local timezone and the format/timezone expected by the API.
 *
 * The API always represents dates in the America/Sao_Paulo timezone, formatted as d/m/Y
 * or d/m/Y H:i:s depending on the endpoint. This class is shared by Filter, which sends
 * dates to the API, and SmsDev, which parses dates back out of API responses.
 */
class ApiDateConverter
{
    public const API_TIMEZONE = 'America/Sao_Paulo';

    /**
     * Converts a date to the format and timezone expected by the API (d/m/Y).
     */
    public static function toApiDateFormat(\DateTimeInterface $date): string
    {
        return self::toApiTimezone($date)->format('d/m/Y');
    }

    /**
     * Converts a time to the format and timezone expected by the API (H:i).
     *
     * Used by the `jobtime` field, paired with toApiDateFormat()'s `jobdate`, to schedule a message.
     */
    public static function toApiTimeFormat(\DateTimeInterface $date): string
    {
        return self::toApiTimezone($date)->format('H:i');
    }

    /**
     * Converts a date to the timezone expected by the API.
     */
    private static function toApiTimezone(\DateTimeInterface $date): \DateTime
    {
        $apiDate = new \DateTime('@'.$date->getTimestamp());

        $apiDate->setTimezone(new \DateTimeZone(self::API_TIMEZONE));

        return $apiDate;
    }

    /**
     * Converts a date string in the API's format and timezone to a \DateTimeInterface in the local timezone.
     *
     * @param string $format Format accepted by \DateTime::createFromFormat(). Defaults to a
     *                       full date and time. Pass a date-only format (e.g. '!d/m/Y') for
     *                       fields that only carry a date, so unspecified time fields don't
     *                       leak the current time into the parsed value.
     *
     * @return \DateTimeInterface|null Null if the value isn't a valid date in the expected format.
     */
    public static function fromApiFormat(string $value, string $format = 'd/m/Y H:i:s'): ?\DateTimeInterface
    {
        $date = \DateTime::createFromFormat($format, $value, new \DateTimeZone(self::API_TIMEZONE));

        if (!$date) {
            return null;
        }

        $date->setTimezone(new \DateTimeZone(\date_default_timezone_get()));

        return $date;
    }
}

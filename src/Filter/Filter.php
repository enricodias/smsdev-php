<?php

namespace enricodias\SmsDev\Filter;

use enricodias\SmsDev\DateTime\ApiDateConverter;

/**
 * Fluent builder for the fetch() search filter.
 *
 * @see https://www.smsdev.com.br/ SMSDev API specification.
 */
class Filter
{
    /**
     * Date format accepted by \date().
     *
     * @var string
     */
    private $dateFormat = 'U';

    /**
     * Query string to be sent to the API as a search filter.
     *
     * The default 'status' = 1 will return all received messages.
     */
    private $query = [
        'status' => 1,
    ];

    public static function create(): self
    {
        return new self();
    }

    /**
     * Sets the date format to be used by dateFrom(), dateTo() and dateBetween().
     *
     * @param string $dateFormat Date format accepted by \date().
     */
    public function setDateFormat(string $dateFormat): self
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    /**
     * Sets the search filter to return unread messages only.
     */
    public function isUnread(): self
    {
        $this->query['status'] = 0;

        return $this;
    }

    /**
     * Sets the search filter to return a message with a specific id.
     */
    public function byId(int $id): self
    {
        if ($id > 0) {
            $this->query['id'] = $id;
        }

        return $this;
    }

    /**
     * Sets the search filter to return messages older than a specific date.
     */
    public function dateFrom(string $date): self
    {
        return $this->parseDate('date_from', $date);
    }

    /**
     * Sets the search filter to return messages newer than a specific date.
     */
    public function dateTo(string $date): self
    {
        return $this->parseDate('date_to', $date);
    }

    /**
     * Sets the search filter to return messages between a specific date interval.
     */
    public function dateBetween(string $dateFrom, string $dateTo): self
    {
        return $this->dateFrom($dateFrom)->dateTo($dateTo);
    }

    /**
     * Returns the search filter as a query string array.
     */
    public function toArray(): array
    {
        return $this->query;
    }

    /**
     * Convert a date to the format supported by the API.
     *
     * The API requires the date format d/m/Y, but any valid date format is supported here.
     * Since the API is always using the timezone America/Sao_Paulo, this function must also
     * do timezone conversions.
     *
     * @see Filter::$dateFormat Date format to be used in all date functions.
     *
     * @param string $key The filter key to be set as a search filter.
     * @param string $date
     */
    private function parseDate(string $key, string $date): self
    {
        $parsedDate = \DateTime::createFromFormat($this->dateFormat, $date);

        if ($parsedDate !== false) {
            $this->query[$key] = ApiDateConverter::toApiFormat($parsedDate);
        }

        return $this;
    }
}

<?php

namespace enricodias;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * SmsDev
 * 
 * Send and receive SMS using SmsDev.com.br
 * 
 * @see https://www.smsdev.com.br/ SMSDev API.
 * 
 * @author Enrico Dias <enrico@enricodias.com>
 */
class SmsDev
{
    /**
     * API url.
     *
     * @var string
     */
    private $_apiUrl = 'https://api.smsdev.com.br';

    /**
     * API key.
     *
     * @var string
     */
    private $_apiKey = '';

    /**
     * API timezone.
     *
     * @object \DateTimeZone
     */
    private $_apiTimeZone;

    /**
     * Date format to be used in all date functions.
     *
     * @var string
     */
    private $_dateFormat = 'U';

    /**
     * Query string to be sent to the API as a search filter.
     *
     * The default 'status' = 1 will return all received messages.
     * 
     * @var array
     */
    private $_query = [
        'status' => 1
    ];

    /**
     * Raw API response/
     *
     * @var array
     */
    private $_result = [];

    /**
     * Creates a new SmsDev instance with an API key and sets the default API timezone.
     *
     * @param string $apiKey
     */
    public function __construct($apiKey = '')
    {
        $this->_apiKey = $apiKey;

        $this->_apiTimeZone = new \DateTimeZone('America/Sao_Paulo');
    }

    /**
     * Send an SMS message.
     * 
     * This method does not guarantee that the recipient received the massage since the message delivery is async.
     * 
     * TODO: verify phone number locally.
     *
     * @param int $number Recipient's number.
     * @param string $message SMS message.
     * @return bool true if the API accepted the request.
     */
    public function send($number, $message)
    {
        $this->_result = [];

        $request = new Request(
            'POST',
            $this->_apiUrl.'/send',
            [
                'Accept' => 'application/json',
            ],
            json_encode([
                'key'    => $this->_apiKey,
                'type'   => 9,
                'number' => $number,
                'msg'    => $message,
            ])
        );

        if ($this->makeRequest($request) === false) return false;

        if ($this->_result['situacao'] !== 'OK') return false;

        return true;
    }

    /**
     * Sets the date format to be used in all date functions.
     *
     * @param string $dateFormat A valid date format (ex: Y-m-d).
     * @return SmsDev Return itself for chaining.
     */
    public function setDateFormat($dateFormat)
    {
        $this->_dateFormat = $dateFormat;

        return $this;
    }

    /**
     * Resets the search filter.
     *
     * @return SmsDev Return itself for chaining.
     */
    public function setFilter()
    {
        $this->_query = [
            'status' => 1
        ];

        return $this;
    }

    /**
     * Sets the search filter to return unread messages only.
     *
     * @return SmsDev Return itself for chaining.
     */
    public function isUnread()
    {
        $this->_query['status'] = 0;

        return $this;
    }

    /**
     * Sets the search filter to return a message with a specific id.
     *
     * @param int $id Message id.
     * @return SmsDev Return itself for chaining.
     */
    public function byId($id)
    {
        $id = intval($id);

        if ($id > 0) $this->_query['id'] = $id;

        return $this;
    }

    /**
     * Sets the search filter to return messages older than a specific date.
     *
     * @param string $date A valid date.
     * @return SmsDev Return itself for chaining.
     */
    public function dateFrom($date)
    {
        return $this->parseDate('date_from', $date);
    }

    /**
     * Sets the search filter to return messages newer than a specific date.
     *
     * @param string $date A valid date.
     * @return SmsDev Return itself for chaining.
     */
    public function dateTo($date)
    {
        return $this->parseDate('date_to', $date);
    }

    /**
     * Sets the search filter to return messages between a specific date interval.
     *
     * @param string $dateFrom Minimum date.
     * @param string $dateTo Maximum date.
     * @return SmsDev Return itself for chaining.
     */
    public function dateBetween($dateFrom, $dateTo)
    {
        return $this->dateFrom($dateFrom)->dateTo($dateTo);
    }

    /**
     * Query the API for received messages using search filters.
     * 
     * @see SmsDev::$_query Search filters.
     * @see SmsDev::$_result API response.
     *
     * @return bool True if the request if the API response is valid.
     */
    public function fetch()
    {
        $this->_result = [];

        $this->_query['key'] = $this->_apiKey;

        $request = new Request(
            'GET',
            $this->_apiUrl.'/get',
            [
                'query'  => $this->_query,
                'Accept' => 'application/json',
            ]
        );

        if ($this->makeRequest($request) === false) return false;

        // resets the filters
        $this->setFilter();

        if (is_array($this->_result)) return true;

        return false;
    }

    /**
     * Parse the received messages in a more useful format with the fields date, number and message.
     * 
     * The dates received by the API are converted to SmsDev::$_dateFormat.
     *
     * @see SmsDev::$_dateFormat Date format to be used in all date functions.
     * 
     * @return array List of received messages.
     */
    public function parsedMessages()
    {
        $localTimeZone = new \DateTimeZone(date_default_timezone_get());

        $messages = [];

        foreach ($this->_result as $key => $result) {

            if (array_key_exists('id_sms_read', $result) === false) continue;

            $id = $result['id_sms_read'];
            $date = \DateTime::createFromFormat('d/m/Y H:i:s', $result['data_read'], $this->_apiTimeZone);

            $date->setTimezone($localTimeZone);

            $messages[$id] = [
                'date'    => $date->format($this->_dateFormat),
                'number'  => $result['telefone'],
                'message' => $result['descricao'],
            ];

        }

        return $messages;
    }

    /**
     * Get the current balance/credits.
     *
     * @return int Current balance in BRL cents.
     */
    public function getBalance()
    {
        $this->_result = [];

        $request = new Request(
            'GET',
            $this->_apiUrl.'/get',
            [
                'Accept' => 'application/json',
            ],
            json_encode([
                'key'    => $this->_apiKey,
                'action' => 'saldo',
            ])
        );

        $this->makeRequest($request);

        if (array_key_exists('saldo_sms', $this->_result) === false) return 0;

        return (int) $this->_result['saldo_sms'];
    }

    /**
     * Get the raw API response from the last response received.
     *
     * @see SmsDev::$_result Raw API response.
     * 
     * @return array Raw API response.
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * Convert a date to format supported by the API.
     * 
     * The API requires the date format d/m/Y, but in this class any valid date format is supported.
     * Since the API is always using the timezone America/Sao_Paulo, this function must also do timezone conversions.
     *
     * @see SmsDev::$_dateFormat Date format to be used in all date functions.
     * 
     * @param string $key The filter key to be set as a search filter.
     * @param string $date A valid date format.
     * @return SmsDev Return itself for chaining.
     */
    private function parseDate($key, $date)
    {
        $parsedDate = \DateTime::createFromFormat($this->_dateFormat, $date);

        if ($parsedDate !== false) {
            
            $parsedDate->setTimezone($this->_apiTimeZone);

            $this->_query[$key] = $parsedDate->format('d/m/Y');

        }

        return $this;
    }

    /**
     * Sends a request to the smsdev.com.br API.
     *
     * @param \GuzzleHttp\Psr7\Request $request Request object.
     * @return bool True if the API response is valid.
     */
    private function makeRequest($request)
    {
        $client = $this->getGuzzleClient();

        try {

            $response = $client->send($request);

        } catch (\Exception $e) {
            
            return false;
            
        }

        $response = json_decode($response->getBody(), true);
        
        if (json_last_error() != JSON_ERROR_NONE || is_array($response) === false) return false;

        $this->_result = $response;

        return true;
    }

    /**
     * Creates GuzzleHttp\Client to be used in API requests.
     * This method is needed to test API calls in unit tests.
     *
     * @return object GuzzleHttp\Client instance.
     * 
     * @codeCoverageIgnore
     */
    protected function getGuzzleClient()
    {
        return new Client();
    }
}
<?php

namespace enricodias\SmsDev;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class SmsDev
{
    private $_apiUrl = 'https://api.smsdev.com.br';

    private $_apiKey = '';

    private $_apiTimeZone;

    private $_dateFormat = 'U';

    private $_query = [
        'status' => 1
    ];

    private $_result = [];

    public function __construct($apiKey = '')
    {
        $this->_apiKey = $apiKey;

        $this->_apiTimeZone = new \DateTimeZone('America/Sao_Paulo');
    }

    /**
     * Send an SMS message.
     * 
     * TODO: verify phone number locally.
     *
     * @param int $number
     * @param string $message
     * @return bool
     */
    public function send($number, $message)
    {
        $this->_result = [];

        $request = new Request(
            'GET',
            $this->_apiUrl.'/send',
            [
                'query' => [
                    'key'    => $this->_apiKey,
                    'type'   => 9,
                    'number' => $number,
                    'msg'    => $message,
                ],
                'Accept' => 'application/json',
            ]
        );

        if ($this->makeRequest($request) === false) return false;

        if ($this->_result['situacao'] !== 'OK') return false;

        return true;
    }

    public function setDateFormat($dateFormat)
    {
        $this->_dateFormat = $dateFormat;

        return $this;
    }

    public function setFilter()
    {
        $this->_query = [
            'status' => 1
        ];

        return $this;
    }

    public function isUnread()
    {
        $this->_query['status'] = 0;

        return $this;
    }

    public function byId($id)
    {
        $id = intval($id);

        if ($id > 0) $this->_query['id'] = $id;

        return $this;
    }

    public function dateFrom($date)
    {
        return $this->parseDate('date_from', $date);
    }

    public function dateTo($date)
    {
        return $this->parseDate('date_to', $date);
    }

    public function dateBetween($dateFrom, $dateTo)
    {
        return $this->dateFrom($dateFrom)->dateTo($dateTo);
    }

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

    public function getBalance()
    {
        $this->_result = [];

        $request = new Request(
            'GET',
            $this->_apiUrl.'/get',
            [
                'query' => [
                    'key'    => $this->_apiKey,
                    'action' => 'saldo',
                ],
                'Accept' => 'application/json',
            ]
        );

        $this->makeRequest($request);

        if (array_key_exists('saldo_sms', $this->_result) === false) return 0;

        return (int) $this->_result['saldo_sms'];
    }

    public function getResult()
    {
        return $this->_result;
    }

    private function parseDate($key, $date)
    {
        $parsedDate = \DateTime::createFromFormat($this->_dateFormat, $date);

        if ($parsedDate !== false) {
            
            $parsedDate->setTimezone($this->_apiTimeZone);

            $this->_query[$key] = $parsedDate->format('d/m/Y');

        }

        return $this;
    }

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
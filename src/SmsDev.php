<?php

namespace enricodias;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * SmsDev.
 *
 * Send and receive SMS using SmsDev.com.br
 *
 * @see https://www.smsdev.com.br/
 *
 * @author Enrico Dias <enrico@enricodias.com>
 */
class SmsDev
{
    /**
     * @var string
     */
    private $apiUrl = 'https://api.smsdev.com.br/v1';

    /**
     * @var string
     */
    private $apiKey = '';

    /**
     * Whether or not to validate phone numbers locally before sending.
     *
     * @var bool
     */
    private $numberValidation = true;

    /**
     * @var \DateTimeZone
     */
    private $apiTimeZone;

    /**
     * Date format to be used in all date functions.
     *
     * @var string
     */
    private $dateFormat = 'U';

    /**
     * Query string to be sent to the API as a search filter.
     *
     * The default 'status' = 1 will return all received messages.
     *
     * @var array
     */
    private $query = [
        'status' => 1
    ];

    /**
     * Raw API response.
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
        $this->apiKey = $apiKey;

        $this->apiTimeZone = new \DateTimeZone('America/Sao_Paulo');
    }

    /**
     * Send an SMS message.
     *
     * This method does not guarantee that the recipient received the massage since the message delivery is async.
     *
     * @param int $number
     * @param string $message
     * @param string $refer (optional) User reference for message identification.
     *
     * @return bool true if the API accepted the request.
     */
    public function send($number, $message, $refer = null)
    {
        $this->_result = [];

        if ($this->numberValidation === true) {
            try {
                $number = $this->validatePhoneNumber($number);
            } catch (\Exception $e) {
                return false;
            } catch (\Throwable $e) {
                return false;
            }
        }

        $params = [
            'key'    => $this->apiKey,
            'type'   => 9,
            'number' => $number,
            'msg'    => $message,
        ];

        if ($refer) $params['refer'] = $refer;

        $request = new Request(
            'POST',
            $this->apiUrl.'/send',
            [
                'Accept' => 'application/json',
            ],
            \json_encode($params)
        );

        if ($this->makeRequest($request) === false || $this->_result['situacao'] !== 'OK') {
            return false;
        }

        return true;
    }

    /**
     * Enables or disables the phone number validation.
     *
     * @param bool $shouldValidate
     *
     * @return void
     */
    public function setNumberValidation($shouldValidate = true)
    {
        $this->numberValidation = (bool) $shouldValidate;
    }

    /**
     * Sets the date format to be used in all date functions.
     *
     * @param string $dateFormat A valid date format (ex: Y-m-d).
     * @return SmsDev
     */
    public function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    /**
     * Resets the search filter.
     *
     * @return SmsDev
     */
    public function setFilter()
    {
        $this->query = [
            'status' => 1,
        ];

        return $this;
    }

    /**
     * Sets the search filter to return unread messages only.
     *
     * @return SmsDev
     */
    public function isUnread()
    {
        $this->query['status'] = 0;

        return $this;
    }

    /**
     * Sets the search filter to return a message with a specific id.
     *
     * @param int $id
     *
     * @return SmsDev
     */
    public function byId($id)
    {
        $id = \intval($id);

        if ($id > 0) {
            $this->query['id'] = $id;
        }

        return $this;
    }

    /**
     * Sets the search filter to return messages older than a specific date.
     *
     * @param string $date
     *
     * @return SmsDev
     */
    public function dateFrom($date)
    {
        return $this->parseDate('date_from', $date);
    }

    /**
     * Sets the search filter to return messages newer than a specific date.
     *
     * @param string $date
     *
     * @return SmsDev
     */
    public function dateTo($date)
    {
        return $this->parseDate('date_to', $date);
    }

    /**
     * Sets the search filter to return messages between a specific date interval.
     *
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return SmsDev
     */
    public function dateBetween($dateFrom, $dateTo)
    {
        return $this->dateFrom($dateFrom)->dateTo($dateTo);
    }

    /**
     * Query the API for received messages using search filters.
     *
     * @see SmsDev::$query Search filters.
     * @see SmsDev::$_result API response.
     *
     * @return bool True if the request was successful.
     */
    public function fetch()
    {
        $this->_result = [];

        $this->query['key'] = $this->apiKey;

        $request = new Request(
            'GET',
            $this->apiUrl.'/inbox',
            [
                'Accept' => 'application/json',
            ],
            json_encode(
                $this->query
            )
        );

        if ($this->makeRequest($request) === false) {
            return false;
        }

        // resets the filters
        $this->setFilter();

        if (\is_array($this->_result) === true) {
            return true;
        }

        return false;
    }

    /**
     * Parse the received messages in a more useful format with the fields date, number and message.
     *
     * The dates received by the API are converted to SmsDev::$dateFormat.
     *
     * @see SmsDev::$dateFormat Date format to be used in all date functions.
     *
     * @return array List of received messages.
     */
    public function parsedMessages()
    {
        $localTimeZone = new \DateTimeZone(\date_default_timezone_get());

        $messages = [];

        foreach ($this->_result as $key => $result) {
            if (\is_array($result) === false || \array_key_exists('id_sms_read', $result) === false) {
                continue;
            }

            $id = $result['id_sms_read'];
            $date = \DateTime::createFromFormat('d/m/Y H:i:s', $result['data_read'], $this->apiTimeZone);

            $date->setTimezone($localTimeZone);

            $messages[$id] = [
                'date'    => $date->format($this->dateFormat),
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
            $this->apiUrl.'/balance',
            [
                'Accept' => 'application/json',
            ],
            \json_encode([
                'key'    => $this->apiKey,
                'action' => 'saldo',
            ])
        );

        $this->makeRequest($request);

        if (\array_key_exists('saldo_sms', $this->_result) === false) {
            return 0;
        }

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
     * Verifies if a phone number is valid.
     *
     * @see https://github.com/giggsey/libphonenumber-for-php libphonenumber for PHP repository.
     *
     * @param int $number
     *
     * @return int A valid mobile phone number.
     *
     * @throws \libphonenumber\NumberParseException If the number is not valid.
     * @throws \Exception If the number is not a valid brazilian mobile number.
     */
    private function validatePhoneNumber($number)
    {
        if (\class_exists('\libphonenumber\PhoneNumberUtil')) {
            $phoneNumberUtil = /** @scrutinizer ignore-call */ \libphonenumber\PhoneNumberUtil::getInstance();
            $mobilePhoneNumber = /** @scrutinizer ignore-call */ \libphonenumber\PhoneNumberType::MOBILE;

            $phoneNumberObject = $phoneNumberUtil->parse($number, 'BR');

            if ($phoneNumberUtil->isValidNumber($phoneNumberObject) === false || $phoneNumberUtil->getNumberType($phoneNumberObject) !== $mobilePhoneNumber) {
                throw new \Exception('Invalid phone number.');
            }

            $number = $phoneNumberObject->getCountryCode().$phoneNumberObject->getNationalNumber();
        }

        return (int) $number;
    }

    /**
     * Convert a date to format supported by the API.
     *
     * The API requires the date format d/m/Y, but in this class any valid date format is supported.
     * Since the API is always using the timezone America/Sao_Paulo, this function must also do timezone conversions.
     *
     * @see SmsDev::$dateFormat Date format to be used in all date functions.
     *
     * @param string $key The filter key to be set as a search filter.
     * @param string $date
     *
     * @return SmsDev
     */
    private function parseDate($key, $date)
    {
        $parsedDate = \DateTime::createFromFormat($this->dateFormat, $date);

        if ($parsedDate !== false) {
            $parsedDate->setTimezone($this->apiTimeZone);

            $this->query[$key] = $parsedDate->format('d/m/Y');
        }

        return $this;
    }

    /**
     * Sends a request to the smsdev.com.br API.
     *
     * @param \GuzzleHttp\Psr7\Request $request
     *
     * @return bool
     */
    private function makeRequest($request)
    {
        $client = $this->getGuzzleClient();

        try {
            $response = $client->send($request);
        } catch (\Exception $e) {
            return false;
        }

        $response = \json_decode($response->getBody(), true);

        if (\json_last_error() !== JSON_ERROR_NONE || \is_array($response) === false) {
            return false;
        }

        $this->_result = $response;

        return true;
    }

    /**
     * Creates GuzzleHttp\Client to be used in API requests.
     * This method is needed to test API calls in unit tests.
     *
     * @return object \GuzzleHttp\Client
     *
     * @codeCoverageIgnore
     */
    protected function getGuzzleClient()
    {
        return new Client();
    }
}

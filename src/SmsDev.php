<?php

namespace enricodias\SmsDev;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
    private const API_BASE_URL = 'https://api.smsdev.com.br/v1';

    /**
     * @var string
     */
    private $apiKey;

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
     * PSR-18 HTTP client used to send requests to the API.
     *
     * @var ClientInterface|null
     */
    private $httpClient;

    /**
     * PSR-17 factory used to build requests.
     *
     * @var RequestFactoryInterface|null
     */
    private $requestFactory;

    /**
     * PSR-17 factory used to build request bodies.
     *
     * @var StreamFactoryInterface|null
     */
    private $streamFactory;

    /**
     * PSR-3 logger used to record debug and usage data.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Creates a new SmsDev instance with an API key and sets the default API timezone.
     *
     * The HTTP client and PSR-17 factories are optional. When not provided, they are resolved
     * through auto discovery, so any PSR-18 client and PSR-17 factories installed on the project
     * will be used automatically.
     *
     * @param string $apiKey
     * @param LoggerInterface|null $logger
     * @param ClientInterface|null $httpClient
     * @param RequestFactoryInterface|null $requestFactory
     * @param StreamFactoryInterface|null $streamFactory
     */
    public function __construct(
        string $apiKey,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?LoggerInterface $logger = null
    ) {
        $this->apiKey = $apiKey;
        $this->apiTimeZone = new \DateTimeZone('America/Sao_Paulo');

        $this->httpClient = $httpClient !== null ? $httpClient : Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory !== null ? $requestFactory : Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory !== null ? $streamFactory : Psr17FactoryDiscovery::findStreamFactory();
        $this->logger = $logger !== null ? $logger : new NullLogger();
    }

    /**
     * Send an SMS message.
     *
     * This method does not guarantee that the recipient received the massage since the message delivery is async.
     *
     * @param string|null $number
     * @param string $message
     * @param string|null $refer (optional) User reference for message identification.
     *
     * @return bool true if the API accepted the request.
     */
    public function send(?string $number, string $message, ?string $refer = null): bool
    {
        $this->_result = [];

        if ($this->numberValidation === true) {
            try {
                $number = $this->validatePhoneNumber($number);
            } catch (\Throwable $e) {
                $this->logger->warning('Invalid phone number.', [
                    'number' => $number,
                    'reason' => $e->getMessage(),
                ]);

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

        $request = $this->buildRequest('POST', self::API_BASE_URL.'/send', $params);

        if ($this->makeRequest($request) === false || $this->_result['situacao'] !== 'OK') {
            $this->logger->error('Failed to send SMS message.', [
                'number' => $number,
                'refer'  => $refer,
                'result' => $this->_result,
            ]);

            return false;
        }

        $this->logger->info('SMS message sent.', [
            'number' => $number,
            'message' => $message,
            'refer'  => $refer,
        ]);

        return true;
    }

    /**
     * Enables or disables the phone number validation.
     */
    public function setNumberValidation(bool $shouldValidate = true): void
    {
        $this->numberValidation = $shouldValidate;
    }

    /**
     * Sets the date format to be used in all date functions.
     *
     * @param string $dateFormat A valid date format (ex: Y-m-d).
     */
    public function setDateFormat(string $dateFormat): self
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    /**
     * Resets the search filter.
     */
    public function setFilter(): self
    {
        $this->query = [
            'status' => 1,
        ];

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
     *
     * @return SmsDev
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
     *
     * @param string $date
     *
     * @return SmsDev
     */
    public function dateFrom(string $date): self
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
    public function dateTo(string $date): self
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
    public function dateBetween(string $dateFrom, string $dateTo): self
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
    public function fetch(): bool
    {
        $this->_result = [];

        $this->query['key'] = $this->apiKey;

        $request = $this->buildRequest('GET', self::API_BASE_URL.'/inbox', $this->query);

        if ($this->makeRequest($request) === false) {
            $this->logger->error('Failed to fetch messages.', [
                'filters' => $this->query,
            ]);

            return false;
        }

        // resets the filters
        $this->setFilter();

        if (\is_array($this->_result) === true) {
            $this->logger->info('Messages fetched.', [
            'filters' => $this->query,
                'count' => \count($this->_result),
            ]);

            return true;
        }

        $this->logger->error('Unexpected API response while fetching messages.', [
            'filters' => $this->query,
            'result' => $this->_result,
        ]);

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
    public function parsedMessages(): array
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
    public function getBalance(): int
    {
        $this->_result = [];

        $request = $this->buildRequest('GET', self::API_BASE_URL.'/balance', [
            'key'    => $this->apiKey,
            'action' => 'saldo',
        ]);

        $this->makeRequest($request);

        if (\array_key_exists('saldo_sms', $this->_result) === false) {
            $this->logger->error('Failed to fetch balance.', [
                'result' => $this->_result,
            ]);

            return 0;
        }

        $balance = (int) $this->_result['saldo_sms'];

        $this->logger->info('Balance fetched.', [
            'balance' => $balance,
        ]);

        return $balance;
    }

    /**
     * Get the raw API response from the last response received.
     *
     * @see SmsDev::$_result Raw API response.
     *
     * @return array Raw API response.
     */
    public function getResult(): array
    {
        return $this->_result;
    }

    /**
     * Verifies if a phone number is valid.
     *
     * @see https://github.com/giggsey/libphonenumber-for-php libphonenumber for PHP repository.
     *
     * @param string|null $number
     *
     * @return int A valid mobile phone number.
     *
     * @throws \libphonenumber\NumberParseException If the number is not valid.
     * @throws \Exception If the number is not a valid brazilian mobile number.
     */
    private function validatePhoneNumber(?string $number): int
    {
        if (\class_exists('\libphonenumber\PhoneNumberUtil') === true) {
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
     */
    private function parseDate(string $key, string $date): self
    {
        $parsedDate = \DateTime::createFromFormat($this->dateFormat, $date);

        if ($parsedDate !== false) {
            $parsedDate->setTimezone($this->apiTimeZone);

            $this->query[$key] = $parsedDate->format('d/m/Y');
        }

        return $this;
    }

    /**
     * Builds a PSR-7 request to be sent to the API.
     */
    private function buildRequest(string $method, string $uri, array $params): RequestInterface
    {
        $body = $this->streamFactory->createStream(\json_encode($params));

        return $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('Accept', 'application/json')
            ->withBody($body);
    }

    /**
     * Sends a request to the smsdev.com.br API.
     */
    private function makeRequest(RequestInterface $request): bool
    {
        $this->logger->debug('Sending request to the SmsDev API.', [
            'method' => $request->getMethod(),
            'uri'    => (string) $request->getUri(),
            'body'   => (string) $request->getBody(),
        ]);

        $request->getBody()->rewind();

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send request to the SmsDev API.', [
                'method' => $request->getMethod(),
                'uri'    => (string) $request->getUri(),
                'reason' => $e->getMessage(),
            ]);

            return false;
        }

        $body = (string) $response->getBody();

        $this->logger->debug('Received response from the SmsDev API.', [
            'status' => $response->getStatusCode(),
            'body'   => $body,
        ]);

        $response = \json_decode($body, true);

        if (\json_last_error() !== JSON_ERROR_NONE || \is_array($response) === false) {
            $this->logger->error('Invalid JSON response from the SmsDev API.', [
                'body' => $body,
            ]);

            return false;
        }

        $this->_result = $response;

        return true;
    }
}

<?php

namespace enricodias\SmsDev;

use enricodias\SmsDev\Exceptions\ApiException;
use enricodias\SmsDev\Exceptions\InvalidResponseException;
use enricodias\SmsDev\Exceptions\TransportException;
use enricodias\SmsDev\Http\ApiClient;
use enricodias\SmsDev\Http\RequestBuilder;
use enricodias\SmsDev\Result\Balance;
use enricodias\SmsDev\Result\ResponseMessage;
use enricodias\SmsDev\Result\SendResult;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
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
     * Builds PSR-7 requests to be sent to the API.
     *
     * @var RequestBuilder
     */
    private $requestBuilder;

    /**
     * Sends requests to the API and decodes the JSON response body.
     *
     * @var ApiClient
     */
    private $apiClient;

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

        $httpClient = $httpClient !== null ? $httpClient : Psr18ClientDiscovery::find();
        $requestFactory = $requestFactory !== null ? $requestFactory : Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = $streamFactory !== null ? $streamFactory : Psr17FactoryDiscovery::findStreamFactory();
        $this->logger = $logger !== null ? $logger : new NullLogger();

        $this->requestBuilder = new RequestBuilder($requestFactory, $streamFactory);
        $this->apiClient = new ApiClient($httpClient, $this->logger);
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
     * @return SendResult[] One SendResult per recipient. A single message still returns a
     *                      one-element array. Per-item failures are reported on the
     *                      SendResult itself, they do not throw.
     *
     * @throws TransportException If the PSR-18 client fails to send the request.
     * @throws InvalidResponseException If the response body is not valid JSON or not the expected shape.
     */
    public function send(?string $number, string $message, ?string $refer = null): array
    {
        $this->_result = [];

        if ($this->numberValidation) {
            try {
                $number = $this->validatePhoneNumber($number);
            } catch (\Throwable $e) {
                $this->logger->warning('Invalid phone number.', [
                    'number' => $number,
                    'reason' => $e->getMessage(),
                ]);

                return [];
            }
        }

        $params = [
            'key'    => $this->apiKey,
            'type'   => 9,
            'number' => $number,
            'msg'    => $message,
        ];

        if ($refer) $params['refer'] = $refer;

        $request = $this->requestBuilder->build('POST', self::API_BASE_URL.'/send', $params);

        $this->_result = $this->apiClient->send($request);

        $results = $this->buildSendResults($this->_result);

        $firstResult = $results[0] ?? null;

        if ($firstResult === null || !$firstResult->isSuccess()) {
            $this->logger->error('Failed to send SMS message.', [
                'number' => $number,
                'refer'  => $refer,
                'result' => $this->_result,
            ]);

            return $results;
        }

        $this->logger->info('SMS message sent.', [
            'number' => $number,
            'message' => $message,
            'refer'  => $refer,
        ]);

        return $results;
    }

    /**
     * Normalizes a send() style response into an array of SendResult.
     *
     * The API returns a bare object when there is exactly one item in the response, and
     * only wraps results in an array when there is more than one item.
     *
     * @param array $result
     *
     * @return SendResult[]
     */
    private function buildSendResults(array $result): array
    {
        if (\array_key_exists('situacao', $result)) {
            return [SendResult::fromArray($result)];
        }

        $results = [];

        foreach ($result as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $results[] = SendResult::fromArray($item);
        }

        return $results;
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
     * @return ResponseMessage[] List of received messages.
     *
     * @throws TransportException If the PSR-18 client fails to send the request.
     * @throws InvalidResponseException If the response body is not valid JSON or not the expected shape.
     */
    public function fetch(): array
    {
        $this->_result = [];

        $this->query['key'] = $this->apiKey;

        $request = $this->requestBuilder->build('GET', self::API_BASE_URL.'/inbox', $this->query);

        $this->_result = $this->apiClient->send($request);

        // resets the filters
        $this->setFilter();

        $messages = $this->buildResponseMessages($this->_result);

        $this->logger->info('Messages fetched.', [
            'filters' => $this->query,
            'count' => \count($messages),
        ]);

        return $messages;
    }

    /**
     * Builds an array of ResponseMessage from a decoded fetch() API response.
     *
     * Dates are converted from the API timezone (America/Sao_Paulo) to the local timezone.
     *
     * @param array $result
     *
     * @return ResponseMessage[]
     */
    private function buildResponseMessages(array $result): array
    {
        $localTimeZone = new \DateTimeZone(\date_default_timezone_get());

        $messages = [];

        foreach ($result as $item) {
            if (\is_array($item) === false || \array_key_exists('id_sms_read', $item) === false) {
                continue;
            }

            $dataRead = \DateTime::createFromFormat('d/m/Y H:i:s', $item['data_read'], $this->apiTimeZone);

            if ($dataRead !== false) {
                $dataRead->setTimezone($localTimeZone);
                $item['data_read'] = $dataRead;
            }

            $messages[] = ResponseMessage::fromArray($item);
        }

        return $messages;
    }

    /**
     * Get the current balance/credits.
     *
     * @return Balance Current balance in BRL cents.
     *
     * @throws TransportException If the PSR-18 client fails to send the request.
     * @throws InvalidResponseException If the response body is not valid JSON or not the expected shape.
     * @throws ApiException If the API reports a failure (situacao other than "OK").
     */
    public function getBalance(): Balance
    {
        $this->_result = [];

        $request = $this->requestBuilder->build('GET', self::API_BASE_URL.'/balance', [
            'key' => $this->apiKey,
        ]);

        $this->_result = $this->apiClient->send($request);

        $balance = Balance::fromArray($this->_result);

        if (!$balance->isSuccess()) {
            $this->logger->error('Failed to fetch balance.', [
                'result' => $this->_result,
            ]);

            throw new ApiException('', $balance->getDescricao() ?? '');
        }

        $this->logger->info('Balance fetched.', [
            'balance' => $balance->getSaldoSms(),
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
}

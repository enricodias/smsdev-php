<?php

namespace enricodias\SmsDev;

use enricodias\SmsDev\Exceptions\ApiException;
use enricodias\SmsDev\Exceptions\InvalidPhoneNumberException;
use enricodias\SmsDev\Exceptions\InvalidResponseException;
use enricodias\SmsDev\Exceptions\TransportException;
use enricodias\SmsDev\Http\ApiClient;
use enricodias\SmsDev\Http\RequestBuilder;
use enricodias\SmsDev\DateTime\ApiDateConverter;
use enricodias\SmsDev\Result\Balance;
use enricodias\SmsDev\Result\MessageResult;
use enricodias\SmsDev\Result\Report;
use enricodias\SmsDev\Result\ResponseMessage;
use enricodias\SmsDev\Result\StatusResult;
use enricodias\SmsDev\Filter\Filter;
use enricodias\SmsDev\Validator\PhoneNumberValidator;
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
     * Search filter used by fetch().
     *
     * @var Filter
     */
    private $filter;

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
     * Validates and normalizes phone numbers before sending, when enabled.
     *
     * @var PhoneNumberValidator
     */
    private $phoneNumberValidator;

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
        $this->filter = new Filter();

        $httpClient = $httpClient !== null ? $httpClient : Psr18ClientDiscovery::find();
        $requestFactory = $requestFactory !== null ? $requestFactory : Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = $streamFactory !== null ? $streamFactory : Psr17FactoryDiscovery::findStreamFactory();
        $this->logger = $logger !== null ? $logger : new NullLogger();

        $this->requestBuilder = new RequestBuilder($requestFactory, $streamFactory);
        $this->apiClient = new ApiClient($httpClient, $this->logger);
        $this->phoneNumberValidator = new PhoneNumberValidator();
    }

    /**
     * Send an SMS message.
     *
     * This method does not guarantee that the recipient received the massage since the message delivery is async.
     *
     * @param string $number
     * @param string $message
     * @param string|null $refer (optional) User reference for message identification.
     *
     * @return MessageResult[] One MessageResult per recipient. A single message still returns a
     *                         one-element array. Per-item failures are reported on the
     *                         MessageResult itself, they do not throw.
     *
     * @throws InvalidPhoneNumberException
     * @throws TransportException
     * @throws InvalidResponseException
     */
    public function send(string $number, string $message, ?string $refer = null): array
    {
        $this->_result = [];

        if ($this->numberValidation) {
            try {
                $number = (string) $this->phoneNumberValidator->validate($number);
            } catch (InvalidPhoneNumberException $e) {
                $this->logger->warning('Invalid phone number.', [
                    'number' => $number,
                    'reason' => $e->getMessage(),
                ]);

                throw $e;
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

        $results = $this->buildMessageResults($this->_result);

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
     * Cancel a previously queued message.
     *
     * Only works while the message status is still queued, before dispatch.
     *
     * @param int|string|array $id Message id, or an array of ids to cancel multiple messages.
     *
     * @return MessageResult[] One MessageResult per id. A single id still returns a
     *                         one-element array. Per-item failures are reported on the
     *                         MessageResult itself, they do not throw.
     *
     * @throws TransportException
     * @throws InvalidResponseException
     */
    public function cancel($id): array
    {
        $this->_result = [];

        $params = [
            'key' => $this->apiKey,
            'id'  => $id,
        ];

        $request = $this->requestBuilder->build('POST', self::API_BASE_URL.'/cancel', $params);

        $this->_result = $this->apiClient->send($request);

        $results = $this->buildMessageResults($this->_result);

        $firstResult = $results[0] ?? null;

        if ($firstResult === null || !$firstResult->isSuccess()) {
            $this->logger->error('Failed to cancel message.', [
                'id'     => $id,
                'result' => $this->_result,
            ]);

            return $results;
        }

        $this->logger->info('Message cancelled.', [
            'id' => $id,
        ]);

        return $results;
    }

    /**
     * Normalizes a send() or cancel() style response into an array of MessageResult.
     *
     * The API returns a bare object when there is exactly one item in the response, and
     * only wraps results in an array when there is more than one item.
     *
     * @return MessageResult[]
     */
    private function buildMessageResults(array $result): array
    {
        if (\array_key_exists('situacao', $result)) {
            return [MessageResult::fromArray($result)];
        }

        $results = [];

        foreach ($result as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $results[] = MessageResult::fromArray($item);
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
     * Sets the search filter to be used by fetch().
     */
    public function setFilter(Filter $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Query the API for received messages using the search filter set by setFilter().
     *
     * @see SmsDev::setFilter() Search filter.
     *
     * @return ResponseMessage[] List of received messages.
     *
     * @throws TransportException
     * @throws InvalidResponseException
     */
    public function fetch(): array
    {
        $this->_result = [];

        $query = $this->filter->toArray();
        $query['key'] = $this->apiKey;

        $request = $this->requestBuilder->build('GET', self::API_BASE_URL.'/inbox', $query);

        $this->_result = $this->apiClient->send($request);

        // resets the filter to the default (all messages)
        $this->filter = new Filter();

        $messages = $this->buildResponseMessages($this->_result);

        $this->logger->info('Messages fetched.', [
            'filters' => $query,
            'count' => \count($messages),
        ]);

        return $messages;
    }

    /**
     * Builds an array of ResponseMessage from a decoded fetch() API response.
     *
     * Dates are converted from the API timezone (America/Sao_Paulo) to the local timezone.
     *
     * @return ResponseMessage[]
     */
    private function buildResponseMessages(array $result): array
    {
        $messages = [];

        foreach ($result as $item) {
            if (\is_array($item) === false || \array_key_exists('id_sms_read', $item) === false) {
                continue;
            }

            $item['data_read'] = $this->convertApiDate($item['data_read'], 'data_read');

            $messages[] = ResponseMessage::fromArray($item);
        }

        return $messages;
    }

    /**
     * Converts a date string in the API's format (America/Sao_Paulo timezone) to a
     * \DateTimeInterface in the local timezone.
     *
     * @param string $value
     * @param string $field Name of the field being converted, used for logging context
     *                      when the value can't be parsed.
     * @param string $format Format accepted by \DateTime::createFromFormat(). Defaults to a
     *                       full date and time. Pass a date-only format (e.g. '!d/m/Y') for
     *                       fields that only carry a date, so unspecified time fields don't
     *                       leak the current time into the parsed value.
     */
    private function convertApiDate(string $value, string $field, string $format = 'd/m/Y H:i:s'): ?\DateTimeInterface
    {
        $date = ApiDateConverter::fromApiFormat($value, $format);

        if ($date === null) {
            $this->logger->warning('Failed to parse date from API response.', [
                'field' => $field,
                'value' => $value,
            ]);
        }

        return $date;
    }

    /**
     * Get the current balance/credits.
     *
     * @throws TransportException
     * @throws InvalidResponseException
     * @throws ApiException
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
     * Query the delivery status of a previously sent message.
     *
     * Only a single id is supported. The API's documented response for this endpoint does
     * not include an id field, so there is currently no reliable way to correlate results
     * back to specific ids when querying more than one at a time.
     *
     * @param int|string $id Message id, as returned by send().
     *
     * @return StatusResult
     *
     * @throws \InvalidArgumentException If an array of ids is passed.
     * @throws TransportException
     * @throws InvalidResponseException
     * @throws ApiException
     */
    public function getStatus($id): StatusResult
    {
        if (\is_array($id)) {
            throw new \InvalidArgumentException('getStatus() only supports a single id.');
        }

        $this->_result = [];

        $request = $this->requestBuilder->build('POST', self::API_BASE_URL.'/dlr', [
            'key' => $this->apiKey,
            'id'  => $id,
        ]);

        $this->_result = $this->apiClient->send($request);

        $result = $this->_result;

        if (\array_key_exists('data_envio', $result)) {
            $result['data_envio'] = $this->convertApiDate($result['data_envio'], 'data_envio');
        }

        $status = StatusResult::fromArray($result);

        if (!$status->isSuccess()) {
            $this->logger->error('Failed to fetch message status.', [
                'id'     => $id,
                'result' => $this->_result,
            ]);

            throw new ApiException('', $status->getDescricao() ?? '');
        }

        $this->logger->info('Message status fetched.', [
            'id' => $id,
        ]);

        return $status;
    }

    /**
     * Get a summarized usage report for a period.
     *
     * @throws TransportException
     * @throws InvalidResponseException
     * @throws ApiException
     */
    public function getReport(\DateTimeInterface $dateFrom, \DateTimeInterface $dateTo): Report
    {
        $this->_result = [];

        $params = [
            'key'       => $this->apiKey,
            'date_from' => ApiDateConverter::toApiFormat($dateFrom),
            'date_to'   => ApiDateConverter::toApiFormat($dateTo),
        ];

        $request = $this->requestBuilder->build('POST', self::API_BASE_URL.'/report/total', $params);

        $this->_result = $this->apiClient->send($request);

        $result = $this->_result;

        if (\array_key_exists('data_inicio', $result)) {
            $result['data_inicio'] = $this->convertApiDate($result['data_inicio'], 'data_inicio', '!d/m/Y');
        }

        if (\array_key_exists('data_fim', $result)) {
            $result['data_fim'] = $this->convertApiDate($result['data_fim'], 'data_fim', '!d/m/Y');
        }

        $report = Report::fromArray($result);

        if (!$report->isSuccess()) {
            $this->logger->error('Failed to fetch report.', [
                'date_from' => $params['date_from'],
                'date_to'   => $params['date_to'],
                'result'    => $this->_result,
            ]);

            throw new ApiException('', $report->getDescricao() ?? '');
        }

        $this->logger->info('Report fetched.', [
            'date_from' => $params['date_from'],
            'date_to'   => $params['date_to'],
        ]);

        return $report;
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
}

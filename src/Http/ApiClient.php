<?php

namespace enricodias\SmsDev\Http;

use enricodias\SmsDev\Exceptions\InvalidResponseException;
use enricodias\SmsDev\Exceptions\TransportException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Sends requests to the SmsDev API and decodes the JSON response body.
 */
class ApiClient
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Last decoded API response.
     *
     * @var array
     */
    private $lastResult = [];

    public function __construct(ClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Sends a request to the smsdev.com.br API and returns the decoded JSON body.
     *
     * @throws TransportException
     * @throws InvalidResponseException
     */
    public function send(RequestInterface $request): array
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

            throw new TransportException('Failed to send request to the SmsDev API.', 0, $e);
        }

        $body = (string) $response->getBody();

        $this->logger->debug('Received response from the SmsDev API.', [
            'status' => $response->getStatusCode(),
            'body'   => $body,
        ]);

        $decodedBody = \json_decode($body, true);

        if (\json_last_error() !== JSON_ERROR_NONE || !\is_array($decodedBody)) {
            $this->logger->error('Invalid JSON response from the SmsDev API.', [
                'body' => $body,
            ]);

            throw new InvalidResponseException('Invalid JSON response from the SmsDev API.');
        }

        $this->lastResult = $decodedBody;

        return $decodedBody;
    }

    /**
     * Get the raw API response from the last response received.
     */
    public function getLastResult(): array
    {
        return $this->lastResult;
    }
}

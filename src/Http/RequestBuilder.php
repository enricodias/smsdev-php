<?php

namespace enricodias\SmsDev\Http;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Builds PSR-7 requests to be sent to the SmsDev API.
 */
class RequestBuilder
{
    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory)
    {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * Builds a PSR-7 request to be sent to the API.
     */
    public function build(string $method, string $uri, array $params): RequestInterface
    {
        $body = $this->streamFactory->createStream(\json_encode($params));

        return $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('Accept', 'application/json')
            ->withBody($body);
    }
}

# smsdev-php

[![Build Status](https://travis-ci.com/enricodias/smsdev-php.svg?branch=master)](https://travis-ci.com/enricodias/smsdev-php)
[![Code Coverage](https://scrutinizer-ci.com/g/enricodias/smsdev-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/enricodias/smsdev-php/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/enricodias/smsdev-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/enricodias/smsdev-php/?branch=master)
[![Latest version](http://img.shields.io/packagist/v/enricodias/smsdev.svg)](https://packagist.org/packages/enricodias/smsdev)
[![Downloads total](http://img.shields.io/packagist/dt/enricodias/smsdev.svg)](https://packagist.org/packages/enricodias/smsdev)
[![License](http://img.shields.io/packagist/l/enricodias/smsdev.svg)](https://github.com/enricodias/smsdev-php/blob/master/LICENSE)

Send and receive SMS using [SmsDev.com.br](https://www.smsdev.com.br)

## Installation

Require this package with Composer in the root directory of your project.

```bash
composer require enricodias/smsdev
```

This package requires an HTTP client implementing [PSR-18](https://www.php-fig.org/psr/psr-18/) and request/stream factories implementing [PSR-17](https://www.php-fig.org/psr/psr-17/). If your project already has a package providing these, it will be found automatically through [php-http/discovery](https://github.com/php-http/discovery).

Otherwise, you will need to install one, [Guzzle](https://github.com/guzzle/guzzle) for example:

```bash
composer require guzzlehttp/guzzle
```

## Usage

Create a new instance with your API key:

```php
$SmsDev = new \enricodias\SmsDev\SmsDev('API_KEY');
```

#### Using a custom HTTP client

By default, the HTTP client and PSR-17 factories are resolved automatically through [php-http/discovery](https://github.com/php-http/discovery). You can also provide your own PSR-18 client and PSR-17 factories in the constructor:

```php
$SmsDev = new \enricodias\SmsDev\SmsDev(
    'API_KEY',
    $httpClient,     // Psr\Http\Client\ClientInterface
    $requestFactory, // Psr\Http\Message\RequestFactoryInterface
    $streamFactory   // Psr\Http\Message\StreamFactoryInterface
);
```

Any argument left out (or passed as `null`) falls back to auto discovery.

#### Using a logger

This package supports [PSR-3](https://www.php-fig.org/psr/psr-3/) logging. [Monolog](https://github.com/Seldaek/monolog) is a common choice:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('SmsDev');
$logger->pushHandler(new StreamHandler('/var/log/SmsDev.log'));

$SmsDev = new \enricodias\SmsDev\SmsDev(
    'API_KEY',
    null,
    null,
    null,
    $logger // Psr\Log\LoggerInterface
);
```

When no logger is provided, a `Psr\Log\NullLogger` is used and no logs are recorded.

`debug` messages log request/response details for troubleshooting, and `info` messages log SMS usage (messages sent, messages fetched, balance checks). `warning` and `error` messages are logged for invalid phone numbers and failed API requests. Phone numbers and message contents are not redacted from the logs.

### Set any date format to be used in the date filter methods:

```php
$SmsDev->setDateFormat('Y-m-d H:i:s'); // default is 'U', timestamp
```

This affects the input format accepted by `dateFrom()`, `dateTo()` and `dateBetween()`. Dates returned by `fetch()` are typed `\DateTimeInterface` objects instead, see [Parsing the response](#parsing-the-response).

### Sending an SMS message

```php
$results = $SmsDev->send('5511988881111', 'SMS Message', 'Reference Code'); // returns an array of SendResult, one per recipient

foreach ($results as $result) {
    if ($result->isSuccess()) {
        echo $result->getId(); // message id, used later with cancel() or getStatus()

        continue;
    }

    echo $result->getDescricao(); // API error message
}

var_dump($SmsDev->getResult()); // Returns the raw API response.
```

A single message still returns a one-element array. Per-item failures are reported on each `SendResult`, `send()` only throws on a total transport/parse failure (see `TransportException` and `InvalidResponseException`).

The country code optional. The default is 55 (Brazil).

#### Phone number validation

If you have the package [giggsey/libphonenumber-for-php](https://github.com/giggsey/libphonenumber-for-php) installed, it will be used to validate numbers locally. You can disable this feature with the method `setNumberValidation` before sending:

```php
$SmsDev->setNumberValidation(false); // disables phone number validation
```

> **SmsDev will charge you for messages sent to invalid numbers.**

### Receiving SMS messages

Every received message is a reply to a message previously sent. You need either the message id or the reference code in order to link the responses with the original message being replied to.

#### Get only unread response messages:

```php
$SmsDev->setFilter()
            ->isUnread()
        ->fetch();
```

#### Get response messages in a specific date interval:

The following date filters are equivalent:

```php
$SmsDev->setDateFormat('Y-m-d');

$SmsDev->setFilter()
            ->dateBetween('2018-01-19', '2019-01-19')
        ->fetch();

$SmsDev->setFilter()
            ->dateBetween('2018-01-19', '')
            ->dateTo('2019-01-19')
        ->fetch();

$SmsDev->setFilter()
            ->dateBetween('', '2019-01-19')
            ->dateFrom('2018-01-19')
        ->fetch();

$SmsDev->setFilter()
            ->dateFrom('2018-01-19')
            ->dateTo('2019-01-19')
        ->fetch();
```

#### Search for a specific message id:

```php
$SmsDev->setFilter()
            ->byId(2515974)
        ->fetch();
```

### Parsing responses

`fetch()` returns an array of `ResponseMessage`, one per message:

```php
$messages = $SmsDev->fetch();

foreach ($messages as $message) {
    echo $message->getDataRead()->format('Y-m-d H:i:s'); // \DateTimeInterface, already converted to the local timezone
    echo $message->getTelefone();
    echo $message->getId();        // Id of the original message
    echo $message->getRefer();     // Reference code of the original message
    echo $message->msgSent();      // Text of the sent message 
    echo $message->idSmsRead();    // ID of the received message
    echo $message->getDescricao(); // Text of the received message
}
```

### Checking the available account balance

```php
$balance = $SmsDev->getBalance();

$balance->saldoSms(); // 123
$balance->getFormattedBalance(); // R$ 1,23
```

## Serialization

All typed responses are `\JsonSerializable`.

## Timezone issues

The API uses the timezone America/Sao_Paulo. Using another timezone in your application will force you to convert dates locally in order to get correct values.

> Ex: if you are using UTC-4 and receive a new message, it will look like the message came from the future because America/Sao_Paulo is UTC-3.

This class solves this problem by automatically correcting dates both in search filters and in parsed messages. Only the dates in raw API responses are not converted.

## TODO

- Check the status of sent messages.
- Send multiple SMS messages.

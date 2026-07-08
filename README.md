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

### Sending an SMS message

```php
$results = $SmsDev->send('5511988881111', 'SMS Message', 'Reference Code'); // returns an array of MessageResult, one per recipient

foreach ($results as $result) {
    if ($result->isSuccess()) {
        echo $result->getId(); // message id, used later with cancel() or getStatus()

        continue;
    }

    echo $result->getDescricao(); // API error message
}

var_dump($SmsDev->getResult()); // Returns the raw API response.
```

A single message still returns a one-element array. Per-item failures are reported on each `MessageResult`.

The country code optional. The default is 55 (Brazil).

#### Phone number validation

If you have the package [giggsey/libphonenumber-for-php](https://github.com/giggsey/libphonenumber-for-php) installed, it will be used to validate numbers locally. `SmsDev::send()` throws `InvalidPhoneNumberException` when the number is invalid, instead of sending the request.

You can disable this feature with the method `setNumberValidation` before sending:

```php
$SmsDev->setNumberValidation(false); // disables phone number validation
```

> **SmsDev will charge you for messages sent to invalid numbers.**

### Cancelling a message

A queued message can be cancelled while it has not been dispatched yet:

```php
$results = $SmsDev->cancel(637849052); // returns an array of MessageResult, one per id

foreach ($results as $result) {
    if ($result->isSuccess()) {
        continue;
    }

    echo $result->getDescricao(); // API error message
}
```

A single id still returns a one-element array. Per-item failures are reported on each `MessageResult`.

Multiple messages can be cancelled in a single request by passing an array of ids:

```php
$results = $SmsDev->cancel([637849052, 637849053]);
```

### Checking a message status

The delivery status (DLR) of a previously sent message can be queried using its id:

```php
$status = $SmsDev->getStatus(637849052);

echo $status->getDescricao(); // delivery status: RECEBIDA, ENVIADA, FILA, CANCELADA, BLACK LIST, APROVACAO or ERRO
echo $status->getOperadora(); // carrier of the recipient phone
echo $status->getDataEnvio()->format('Y-m-d H:i:s'); // \DateTimeInterface, already converted to the local timezone
```

`getStatus()` throws `ApiException` if the query itself fails, e.g. an invalid or unknown id.

> Only a single id is supported since the API's documented response for this endpoint doesn't return an id field to correlate multiple results back to specific ids.

### Receiving SMS messages

Every received message is a reply to a message previously sent. You need either the message id or the reference code in order to link the responses with the original message being replied to.

Search filters are built with `\enricodias\SmsDev\Filter\Filter`, a fluent builder passed into `setFilter()`. Calling `fetch()` with no filter set returns all messages. The filter is reset after every `fetch()` call.

#### Get only unread response messages:

```php
use enricodias\SmsDev\Filter\Filter;

$SmsDev->setFilter(
    Filter::create()
        ->isUnread()
)->fetch();
```

#### Get response messages in a specific date interval:

`Filter::setDateFormat()` sets the input format accepted by `dateFrom()`, `dateTo()` and `dateBetween()` (default is `'U'`, timestamp). Dates returned by `fetch()` are typed `\DateTimeInterface` objects instead, see [Parsing the response](#parsing-the-response).

The following date filters are equivalent:

```php
$SmsDev->setFilter(
    Filter::create()
        ->setDateFormat('Y-m-d')
        ->dateBetween('2018-01-19', '2019-01-19')
)->fetch();

$SmsDev->setFilter(
    Filter::create()
        ->setDateFormat('Y-m-d')
        ->dateBetween('2018-01-19', '')
        ->dateTo('2019-01-19')
)->fetch();

$SmsDev->setFilter(
    Filter::create()
        ->setDateFormat('Y-m-d')
        ->dateBetween('', '2019-01-19')
        ->dateFrom('2018-01-19')
)->fetch();

$SmsDev->setFilter(
    Filter::create()
        ->setDateFormat('Y-m-d')
        ->dateFrom('2018-01-19')
        ->dateTo('2019-01-19')
)->fetch();
```

#### Search for a specific message id:

```php
$SmsDev->setFilter(
    Filter::create()
        ->byId(2515974)
)->fetch();
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

The balance is measured in credits. Sending one SMS consumes one credit.

```php
$balance = $SmsDev->getBalance();

$balance->saldoSms(); // 123
```

### Getting a usage report

A summarized usage report for a period can be retrieved with `SmsDev::getReport()`, using `\DateTimeInterface` objects for the period boundaries:

```php
$report = $SmsDev->getReport(
    new \DateTimeImmutable('2020-01-01'),
    new \DateTimeImmutable('2020-01-30')
);

echo $report->getDataInicio()->format('Y-m-d'); // \DateTimeInterface, already converted to the local timezone
echo $report->getDataFim()->format('Y-m-d');    // \DateTimeInterface, already converted to the local timezone
echo $report->getEnviada();    // messages sent in the period
echo $report->getRecebida();   // messages received in the period
echo $report->getBlacklist();  // messages blocked by the blacklist in the period
echo $report->getCancelada();  // messages cancelled in the period
echo $report->getQtdCredito(); // credits consumed in the period
```

`getReport()` throws `ApiException` if the query itself fails.

## Serialization

All typed responses are `\JsonSerializable`.

## Timezone issues

The API uses the timezone America/Sao_Paulo. Using another timezone in your application will force you to convert dates locally in order to get correct values.

> Ex: if you are using UTC-4 and receive a new message, it will look like the message came from the future because America/Sao_Paulo is UTC-3.

This class solves this problem by automatically correcting dates both in search filters and in parsed messages. Only the dates in raw API responses are not converted.

## TODO

- Send multiple SMS messages.

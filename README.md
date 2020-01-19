# smsdev-php

Send and receive SMS using [SmsDev.com.br](https://www.smsdev.com.br)

## Installation

Require this package with Composer in the root directory of your project.

```bash
composer require enricodias/smsdev-php
```

## Usage

Create a new instance with your API key:

```php
$SmsDev = new \enricodias\SmsDev('API_KEY');
```

Set any date format to be used in all date methods:

```php
$SmsDev->setDateFormat('Y-m-d H:i:s'); // default is 'U', timestamp
```

### Sending an SMS message

```php
$SmsDev->send(5511988881111, 'SMS Message'); // returns true if the API accepts the message

var_dump($SmsDev->getResult()); // Returns the raw API response.
```

The country code optional. The default is 55 (Brazil).

### Receiving SMS messages

Get unread messages in a specific date interval:

```php
$SmsDev->setFilter()
            ->isUnread()
            ->dateBetween('2018-01-19', '2019-01-19')
        ->fetch();
```

Search for a specific message id:

```php
$SmsDev->setFilter()
            ->byId(2515974)
        ->fetch();
```

### Parsing the response

After fetching the messages you can either access the raw API response using ```getResult()``` or use the function ```parsedMessages()``` to get a simplified array:

```php
$SmsDev->setDateFormat('U'); // timestamp

$messages = $SmsDev->parsedMessages();

var_dump($messages);

/*
array(1) {
    ['date']    => '1529418914'
    ['number']  => '5511988887777'
    ['message'] => 'Message'
}
*/
```

Dates are converted to the format specified in ```setDateFormat()```.

### Date filters

The following filters are equivalent:

```php
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

## Timezone problems

The API uses the timezone America/Sao_Paulo. Using another timezone in your application will force you to convert dates locally in order to get correct values.

> Ex: if you are using UTC-4 and receive a new message, it will look like the message came from the future because America/Sao_Paulo is UTC-3.

This class solves this problem by automatically correcting dates both in search filters and in parsed messages. Only the dates in raw API responses are not converted.

## TODO

-    Verify phone number locally.
-    Check the status of sent messages.
-    Send multiple SMS messages.

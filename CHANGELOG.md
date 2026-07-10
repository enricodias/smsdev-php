# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## Unreleased

#### Added
- PSR-17 and PSR-18 support with auto discovery
- PSR-3 logger support
- `Filter\Filter`, a fluent search filter builder for `fetch()`
- `SmsDev::cancel()`, cancelling a previously queued message
- `SmsDev::getStatus()`, querying the delivery status (DLR) of a previously sent message
- `SmsDev::getReport()`, a summarized usage report for a period
- `Message\Message`, a fluent value object describing a message to send
- `SmsDev::sendMultiple()`, send up to 300 messages in a single request
- `SmsDev::send()`'s `$schedule` argument, scheduling a message for later delivery instead of sending immediately
- `Callback\CallbackParser`, parsing an inbound SmsDev callback request body (MO reply or DLR)

#### Fixed
- Account balance is measured in credits, not cents.

#### Changed
- Moved `SmsDev` into the `enricodias\SmsDev` namespace
- `SmsDev::send()` now throws `Exceptions\InvalidPhoneNumberException`
- `SmsDev::send()` now returns an array of `MessageResult` instead of `bool`
- `fetch()` now returns an array of `ResponseMessage` instead of `bool`
- `SmsDev::getBalance()` now returns a `Balance` instead of `int`, and throws `ApiException` instead of `0` on failure
- `SmsDev::setFilter()` now accepts a built `Filter\Filter` instance instead of owning the filter
  state itself, e.g. `$smsDev->setFilter(Filter::create()->isUnread())`
- `SmsDev::send()` now throws `InvalidPhoneNumberException` on a local validation failure instead
  of silently returning an empty array

#### Removed
- PHP 5.6 compactibility
- `SmsDev::parsedMessages()`, replaced by the `ResponseMessage` array returned by `SmsDev::fetch()`
- `SmsDev::setDateFormat()`, `isUnread()`, `byId()`, `dateFrom()`, `dateTo()` and `dateBetween()`,
  replaced by the equivalent methods on `Filter\Filter`
- `SmsDev::validatePhoneNumber()`, replaced by `PhoneNumber\PhoneNumberValidator`

## 0.4

_Released: 2022-06-01_
#### Added

- Add a Changelog
- Add a Code of Conduct
- Add support for the 'refer' parameter
- Add support for guzzle v7.4

## 0.3

_Released: 2020-11-26_

#### Added

- Optional phone number validation using [giggsey/libphonenumber-for-php](https://github.com/giggsey/libphonenumber-for-php)

## 0.2

_Released: 2020-10-19_

#### Fixed

- Fix namespaces
- Fix request body in send, getBalance and fetch methods
- Update API URL and endpoints

## 0.1

_Released: 2020-01-19_

#### Added

- First working version

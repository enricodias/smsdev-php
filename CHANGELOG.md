# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## Unreleased

#### Added
- PSR-17 and PSR-18 support with auto discovery
- PSR-3 logger support
- `Result\SendResult`, `Result\Balance` and `Result\ResponseMessage` typed response objects
- `Balance::getFormattedBalance()`, the balance formatted as Brazilian currency (ex: "R$ 1,23")
- `Exceptions\ApiException`, carrying the API's code and description fields
- `Filter\Filter`, a fluent search filter builder for `fetch()`
- `Validator\PhoneNumberValidator`, extracted from `SmsDev`
- `Exceptions\InvalidPhoneNumberException`, thrown by `send()` when phone number validation is
  enabled and rejects the number

#### Changed
- Moved `SmsDev` into the `enricodias\SmsDev` namespace
- `SmsDev::send()` now returns an array of `SendResult` instead of `bool` (single item normalized
  into a one-element array, per-item failures are reported on each `SendResult` instead of throwing)
- `fetch()` now returns an array of `ResponseMessage` instead of `bool`
- `SmsDev::getBalance()` now returns a `Balance` instead of `int`, and throws `ApiException` instead
  of returning `0` on failure
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

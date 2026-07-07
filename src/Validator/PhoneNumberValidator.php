<?php

namespace enricodias\SmsDev\Validator;

use enricodias\SmsDev\Exceptions\InvalidPhoneNumberException;

/**
 * Validates and normalizes Brazilian mobile phone numbers before sending.
 *
 * @see https://github.com/giggsey/libphonenumber-for-php libphonenumber for PHP repository.
 */
class PhoneNumberValidator
{
    /**
     * Verifies if a phone number is a valid brazilian mobile number.
     *
     * When the optional giggsey/libphonenumber-for-php package is not installed, the number
     * is returned as-is, cast to int, without further validation.
     *
     * @return int A valid mobile phone number.
     *
     * @throws InvalidPhoneNumberException If the number is not a valid brazilian mobile number.
     */
    public function validate(string $number): int
    {
        if (!\class_exists('\libphonenumber\PhoneNumberUtil')) {
            return (int) $number;
        }

        $phoneNumberUtil = /** @scrutinizer ignore-call */ \libphonenumber\PhoneNumberUtil::getInstance();
        $mobilePhoneNumber = /** @scrutinizer ignore-call */ \libphonenumber\PhoneNumberType::MOBILE;

        try {
            $phoneNumberObject = $phoneNumberUtil->parse($number, 'BR');
        } catch (\Throwable $e) {
            throw new InvalidPhoneNumberException('Invalid phone number.', 0, $e);
        }

        $isMobileNumber = $phoneNumberUtil->isValidNumber($phoneNumberObject) &&
                          $phoneNumberUtil->getNumberType($phoneNumberObject) === $mobilePhoneNumber;

        if (!$isMobileNumber) {
            throw new InvalidPhoneNumberException('Invalid phone number.');
        }

        return (int) ($phoneNumberObject->getCountryCode().$phoneNumberObject->getNationalNumber());
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ZimbabwePhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('Phone number is required.');

            return;
        }

        if (! self::isValid(self::normalize($value))) {
            $fail('Enter a valid Zimbabwe mobile number (e.g. +263771234567 or 0771234567).');
        }
    }

    public static function isValid(string $value): bool
    {
        return (bool) preg_match('/^(\+263|263)7[1378]\d{7}$/', $value)
            || (bool) preg_match('/^07[1378]\d{7}$/', $value);
    }

    public static function normalize(string $value): string
    {
        $normalized = preg_replace('/[\s\-()]/', '', trim($value)) ?? '';

        if (preg_match('/^0(7[1378]\d{7})$/', $normalized, $matches)) {
            return '+263'.$matches[1];
        }

        if (preg_match('/^263(7[1378]\d{7})$/', $normalized, $matches)) {
            return '+263'.$matches[1];
        }

        return $normalized;
    }

    public static function toEcocashMsisdn(string $value): string
    {
        return ltrim(self::normalize($value), '+');
    }
}

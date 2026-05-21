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

        $normalized = preg_replace('/[\s\-()]/', '', trim($value));

        if (! is_string($normalized) || ! preg_match('/^\+2637[1378]\d{7}$/', $normalized)) {
            $fail('Enter a valid Zimbabwe mobile number with country code (e.g. +263771234567).');
        }
    }

    public static function normalize(string $value): string
    {
        return preg_replace('/[\s\-()]/', '', trim($value)) ?? '';
    }
}

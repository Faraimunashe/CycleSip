<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MinimumAge implements ValidationRule
{
    public function __construct(
        private readonly int $minimumAge = 18,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! $value instanceof \DateTimeInterface) {
            $fail('A valid date of birth is required.');

            return;
        }

        $dateOfBirth = \Illuminate\Support\Carbon::parse($value);
        $minimumBirthDate = now()->subYears($this->minimumAge)->startOfDay();

        if ($dateOfBirth->greaterThan($minimumBirthDate)) {
            $fail("You must be at least {$this->minimumAge} years old to register.");
        }
    }
}

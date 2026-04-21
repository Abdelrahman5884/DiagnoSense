<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidContactRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        if (preg_match('/^01[0125][0-9]{8}$/', $value)) {
            return;
        }

        $fail('The contact must be a valid email address or a valid phone number starting with 010, 011, 012, or 015 followed by 8 digits.');
    }
}

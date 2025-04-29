<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPanNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Ensure the value is a string and has exactly 10 characters
        if (!is_string($value) || strlen($value) !== 10) {
            $fail('The :attribute must be exactly 10 characters long.');
            return;
        }

        // Validate PAN format: 5 letters, 4 digits, 1 letter
        if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $value)) {
            $fail('The :attribute must follow the format: 5 letters, 4 digits, 1 letter (e.g., ABCDE1234F).');
            return;
        }

        // Validate the fourth letter (taxpayer type)
        $validFourthLetters = ['A', 'B', 'C', 'F', 'G', 'H', 'I', 'J', 'L', 'P', 'T'];
        if (!in_array($value[3], $validFourthLetters)) {
            $fail('The :attribute contains an invalid taxpayer type in the fourth letter.');
            return;
        }

        // Optional: Add checksum validation (if required)
        // PAN checksum is complex and not always publicly documented.
        // You can skip this unless you have a specific algorithm provided by NSDL or another authority.
    }
}

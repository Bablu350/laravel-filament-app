<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ValidIfscCode implements ValidationRule
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
        // Validate IFSC code format
        if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $value)) {
            $fail('The :attribute must be a valid IFSC code (e.g., SBIN0001234).');
            return;
        }

        // Check cache for valid IFSC
        $cacheKey = 'ifsc_' . $value;
        if (Cache::has($cacheKey)) {
            return; // Valid IFSC found in cache
        }

        // Query Razorpay IFSC API
        try {
            $response = Http::timeout(5)->get('https://ifsc.razorpay.com/' . $value);
            if ($response->successful()) {
                $data = $response->json();
                // Cache minimal bank details for 24 hours
                Cache::put($cacheKey, [
                    'bank' => $data['BANK'],
                    'branch' => $data['BRANCH'],
                    'city' => $data['CITY'],
                ], now()->addHours(24));
            } else {
                $fail('The :attribute does not exist.');
            }
        } catch (\Exception $e) {
            // Optionally log the error for debugging
            // \Log::warning('IFSC API error: ' . $e->getMessage());
            $fail('Unable to verify the :attribute. Please try again later.');
        }
    }
}
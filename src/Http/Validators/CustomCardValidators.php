<?php

namespace EleganceCMS\Iyzico\Http\Validators;

use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CustomCardValidators
{
    public static function register()
    {
        Validator::extend('validate_card_number', function ($attribute, $value, $parameters, $validator) {
            $cleanedValue = preg_replace('/\D/', '', $value);

            if (strlen($cleanedValue) < 13 || strlen($cleanedValue) > 19) {
                return false;
            }

            $sum = 0;
            $shouldDouble = false;

            for ($i = strlen($cleanedValue) - 1; $i >= 0; $i--) {
                $digit = (int)$cleanedValue[$i];

                if ($shouldDouble) {
                    $digit *= 2;
                    if ($digit > 9) {
                        $digit -= 9;
                    }
                }

                $sum += $digit;
                $shouldDouble = !$shouldDouble;
            }

            return $sum % 10 === 0;
        }, 'The :attribute must be a valid credit card number.');

        Validator::extend('validate_expiration_date', function ($attribute, $value, $parameters, $validator) {
            $parts = explode('/', str_replace(' ', '', $value));

            if (count($parts) !== 2) {
                return false;
            }

            $month = (int)$parts[0];
            $year = (int)$parts[1];

            if ($year < 100) {
                $year += 2000;
            }

            if ($month < 1 || $month > 12) {
                return false;
            }

            $currentYear = Carbon::now()->year;
            if ($year < $currentYear || ($year === $currentYear && $month < Carbon::now()->month)) {
                return false;
            }

            return true;
        }, 'The :attribute must be a valid expiration date in MM/YY or MM/YYYY format and must not be expired.');
    }
}

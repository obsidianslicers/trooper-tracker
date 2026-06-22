<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

/**
 * Trait to normalize boolean fields in request input.
 *
 * Provides a method to convert specified input fields to boolean values,
 * ensuring consistent data types for validation and processing.
 */
trait HasNormalizers
{
    /**
     * Normalize a phone number by removing all non-digit characters.
     *
     * @param  string  $phone  The phone number to normalize.
     * @return string The normalized phone number containing only digits.
     */
    protected function normalizePhoneInput(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone);
    }

    /**
     * Normalize specified boolean fields in the input array.
     *
     * @param  array  $input  The input data array.
     * @param  array  $boolean_keys  The keys of the fields to normalize as booleans.
     * @return array The input array with normalized boolean fields.
     */
    protected function normalizeRuleKey(string $rule): string
    {
        return explode(':', $rule)[0];
    }

    protected function friendlyPhrase(string $rule): string
    {
        return match ($rule)
        {
            'integer' => 'an integer',
            'string' => 'a valid string',
            default => str_replace(':', ' ', $rule),
        };
    }

    protected function normalizeBooleanFields(array $input, array $boolean_keys, bool $add_missing = false): array
    {
        foreach ($input as $id => $data)
        {
            foreach ($boolean_keys as $key)
            {
                if (array_key_exists($key, $data))
                {
                    $input[$id][$key] = filter_var($data[$key], FILTER_VALIDATE_BOOLEAN);
                }
                elseif ($add_missing)
                {
                    $input[$id][$key] = false;
                }
            }
        }

        return $input;
    }
}

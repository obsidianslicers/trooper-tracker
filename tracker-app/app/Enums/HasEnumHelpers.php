<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Provides common helper methods for backed enums.
 *
 * This trait should be used by all backed string enums in the application to provide
 * consistent methods for converting enums to arrays and validator strings.
 */
trait HasEnumHelpers
{
    /**
     * Return an associative array of enum case value => name.
     *
     * Values are sorted alphabetically by case name and formatted using to_title().
     *
     * @return array<string, string> Array with enum values as keys and formatted names as values
     */
    public static function toArray(): array
    {
        $cases = self::cases();

        // Sort by case->name
        usort($cases, fn ($a, $b) => strcmp($a->name, $b->name));

        $pairs = [];

        foreach ($cases as $case)
        {
            $pairs[$case->value] = to_title($case->name);
        }

        return $pairs;
    }

    /**
     * Return a comma-delimited string of all enum values for use in Laravel validation rules.
     *
     * @return string Comma-separated list of enum values
     */
    public static function toValidator(): string
    {
        $cases = self::cases();

        // Sort by case->name
        usort($cases, fn ($a, $b) => strcmp($a->name, $b->name));

        $values = [];

        foreach ($cases as $case)
        {
            $values[] = $case->value;
        }

        return implode(',', $values);
    }
}

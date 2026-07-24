<?php

declare(strict_types=1);

namespace App\Enums;

use BackedEnum;

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
     * Return an associative array of enum case value => name.
     *
     * Values are sorted alphabetically by case name and formatted using to_title().
     *
     * @return array<string, string> Array with enum values as keys and formatted names as values
     */
    public static function toValueLabels(?array $exclude = null, ?array $include = null): array
    {
        $cases = self::cases();
        $exclude_values = self::normalizeCaseFilters($exclude);
        $include_values = self::normalizeCaseFilters($include);

        // Sort by case->name
        usort($cases, fn ($a, $b) => strcmp($a->name, $b->name));

        $pairs = [];

        foreach ($cases as $case)
        {
            if ($exclude_values && in_array($case->value, $exclude_values, true))
            {
                continue;
            }

            if ($include_values && !in_array($case->value, $include_values, true))
            {
                continue;
            }

            $pairs[] = ['value' => $case->value, 'label' => to_title($case->name)->toString()];
        }

        return $pairs;
    }

    /**
     * @param  array<int, BackedEnum|string>|null  $filters
     * @return array<int, string>|null
     */
    private static function normalizeCaseFilters(?array $filters): ?array
    {
        if ($filters === null)
        {
            return null;
        }

        return array_map(
            static fn (BackedEnum|string $filter): string => $filter instanceof BackedEnum ? (string) $filter->value : $filter,
            $filters
        );
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

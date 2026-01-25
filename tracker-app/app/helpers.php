<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

if (!function_exists('qs'))
{
    /**
     * Build a query string by merging the current request query with overrides.
     *
     * @param  array  $overrides
     * @return array
     */
    function qs(array $overrides = []): array
    {
        return array_merge(request()->query(), $overrides);
    }

    /**
     * Return a Storage URL if the file exists, otherwise fall back to a public asset.
     *
     * @param  string|null  $path   Path relative to the storage disk
     * @param  string       $default Relative path under public/ (e.g. 'img/icons/foo.png')
     * @param  string       $disk   Storage disk to check (default 'public')
     * @return string
     */
    function map_image_url(?string $path, string $default, string $disk = 'public'): string
    {
        if ($path && Storage::disk($disk)->exists($path))
        {
            return Storage::url($path);
        }

        return url($default);
    }

    /**
     * Convert a property.name to a bracketed[name]
     * @param string $property
     * @return string
     */
    function to_bracket_name(string $property): string
    {
        $bracketed = preg_replace('/\.(\d+)/', '[$1]', $property);
        $bracketed = preg_replace('/\.(\w+)/', '[$1]', $bracketed);
        return $bracketed;
    }

    /**
     * Convert a PROPERTY_NAME to a property_name
     * @param string $value
     * @return Stringable
     */
    function to_title(string $value): Stringable
    {
        return Str::of($value)
            ->lower()
            ->replace('_', ' ')
            ->title();
    }
}

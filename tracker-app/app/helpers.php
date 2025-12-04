<?php

declare(strict_types=1);

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (!function_exists('setting'))
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
     * @return array|string|null
     */
    function to_bracket_name(string $property)
    {
        $bracketed = preg_replace('/\.(\d+)/', '[$1]', $property);
        $bracketed = preg_replace('/\.(\w+)/', '[$1]', $bracketed);
        return $bracketed;
    }

    /**
     * Convert a PROPERTY_NAME to a property_name
     * @param string $value
     * @return array|string|null
     */
    function to_title(string $value)
    {
        return Str::of($value)
            ->lower()
            ->replace('_', ' ')
            ->title();
    }

    /**
     * Retrieve a setting value from the database with optional default and type casting.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting(string $key, mixed $default = null): mixed
    {
        $key = strtolower($key);

        $cast = function (mixed $value): mixed
        {
            if (is_null($value))
            {
                return null;
            }

            if (is_numeric($value))
            {
                return str_contains($value, '.') ? (float) $value : (int) $value;
            }

            $lower = strtolower((string) $value);

            return match ($lower)
            {
                'true', '(true)' => true,
                'false', '(false)' => false,
                'null', '(null)' => null,
                default => $value,
            };
        };

        $value = Cache::rememberForever("setting.{$key}", fn() =>
            Setting::find($key)?->value
        );

        return $cast($value ?? $default);
    }
}

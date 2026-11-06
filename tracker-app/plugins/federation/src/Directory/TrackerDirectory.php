<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Federation\Directory;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;
use TroopTracker\Plugins\Federation\Support\ThisTracker;

/**
 * The supported trackers: the list bundled with the plugin plus the shared list
 * in the repository (or the one set on the plugin's settings page). Being on the
 * list only means a tracker can be offered; it grants nothing by itself.
 */
class TrackerDirectory
{
    public const CACHE_KEY = 'federation:directory';

    public function __construct(private readonly ThisTracker $this_tracker) {}

    /**
     * @return Collection<string, DirectoryTracker> keyed by URL, sorted by name
     */
    public function all(): Collection
    {
        $entries = array_merge($this->bundled(), $this->shared());

        return collect($entries)
            ->map(fn (mixed $entry): ?DirectoryTracker => self::parse($entry))
            ->filter()
            ->keyBy(fn (DirectoryTracker $tracker): string => $tracker->url)
            ->sortBy(fn (DirectoryTracker $tracker): string => strtolower($tracker->name));
    }

    /**
     * The trackers a trooper here can link to: every listed tracker but this one.
     *
     * @return Collection<string, DirectoryTracker>
     */
    public function others(): Collection
    {
        return $this->all()->except($this->this_tracker->url());
    }

    public function find(string $url): ?DirectoryTracker
    {
        return $this->all()->get(self::normalizeUrl($url));
    }

    public function listsThisTracker(): bool
    {
        return $this->all()->has($this->this_tracker->url());
    }

    public static function normalizeUrl(string $url): string
    {
        return rtrim(trim($url), '/');
    }

    private static function parse(mixed $entry): ?DirectoryTracker
    {
        if (!is_array($entry) || !is_string($entry['name'] ?? null) || !is_string($entry['url'] ?? null))
        {
            return null;
        }

        $url = self::normalizeUrl($entry['url']);
        $name = trim($entry['name']);

        if ($name === '' || !self::isAllowedUrl($url))
        {
            return null;
        }

        return new DirectoryTracker(mb_substr($name, 0, 128), $url);
    }

    private static function isAllowedUrl(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (filter_var($url, FILTER_VALIDATE_URL) === false || parse_url($url, PHP_URL_HOST) === null)
        {
            return false;
        }

        return $scheme === 'https' || ($scheme === 'http' && app()->environment('local', 'testing'));
    }

    /**
     * @return array<int, mixed>
     */
    private function bundled(): array
    {
        $path = dirname(__DIR__, 2).'/trackers.json';
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        return is_array($data['trackers'] ?? null) ? $data['trackers'] : [];
    }

    /**
     * @return array<int, mixed>
     */
    private function shared(): array
    {
        $url = $this->this_tracker->directoryUrl();

        if ($url === null)
        {
            return [];
        }

        $key = self::CACHE_KEY.':'.sha1($url);
        $cached = Cache::get($key);

        if (is_array($cached))
        {
            return $cached;
        }

        try
        {
            $data = Http::timeout(5)->acceptJson()->get($url)->throw()->json();
        }
        catch (Throwable $e)
        {
            report($e);

            return [];
        }

        $trackers = is_array($data['trackers'] ?? null) ? $data['trackers'] : [];
        Cache::put($key, $trackers, now()->addDay());

        return $trackers;
    }
}

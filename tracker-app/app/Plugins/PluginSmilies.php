<?php

declare(strict_types=1);

namespace App\Plugins;

use Closure;

/**
 * The smilies a plugin offers in the site's markdown editors, such as a forum's.
 */
final class PluginSmilies
{
    /**
     * @var (Closure(): iterable<int, array<string, mixed>>)|null
     */
    private ?Closure $source = null;

    /**
     * @param  Closure(): iterable<int, array<string, mixed>>  $source  Called only when an
     *                                                                  editor is shown; returns rows with a title, code and image_url
     */
    public function set(Closure $source): void
    {
        $this->source = $source;
    }

    /**
     * The smilies to offer, skipping any without a code or picture.
     *
     * @return array<int, array{title:string,code:string,image_url:string}>
     */
    public function all(): array
    {
        if ($this->source === null)
        {
            return [];
        }

        return collect(($this->source)())
            ->map(fn (array $smiley): array => [
                'title' => (string) ($smiley['title'] ?? ''),
                'code' => (string) ($smiley['code'] ?? ''),
                'image_url' => (string) ($smiley['image_url'] ?? ''),
            ])
            ->filter(fn (array $smiley): bool => $smiley['code'] !== '' && $smiley['image_url'] !== '')
            ->values()
            ->all();
    }
}

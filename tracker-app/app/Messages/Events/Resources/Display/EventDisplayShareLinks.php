<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Display;

use App\Facades\Share;

/**
 * The Facebook and X share links for a page, with the icon each one shows.
 */
final class EventDisplayShareLinks
{
    /**
     * @return list<array{provider: string, url: string, icon: string}>
     */
    public static function for(string $url, string $title): array
    {
        $links = (new Share)->page($url, $title)->facebook()->twitter()->getRawLinks();

        return collect((array) $links)
            ->map(fn (string $link, string $provider) => [
                'provider' => $provider,
                'url' => $link,
                'icon' => (string) config("share.{$provider}.fa-icon"),
            ])
            ->values()
            ->all();
    }
}

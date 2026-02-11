<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for social media sharing link generation.
 *
 * @method static Share page(string $url, string $title) Set the page URL and title to share
 * @method static Share currentPage(?string $title = null) Set the current page URL to share
 * @method static Share facebook() Add Facebook share link
 * @method static Share twitter() Add Twitter share link
 * @method static Share reddit() Add Reddit share link
 * @method static Share telegram() Add Telegram share link
 * @method static Share whatsapp() Add WhatsApp share link
 * @method static Share linkedin() Add LinkedIn share link
 * @method static Share pinterest() Add Pinterest share link
 * @method static string|array<string, string> getRawLinks() Get the raw generated links
 *
 * @see Share
 */
class ShareFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Share::class;
    }
}

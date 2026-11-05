<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Something a plugin can do to the site that an administrator should know about
 * before turning it on. A plugin lists the ones it uses in plugin.json
 * "capabilities", and a hook it has not listed refuses to run.
 *
 * This tells administrators what a plugin does and stops a plugin quietly doing
 * more. It is not a sandbox: a plugin is PHP code with the app's full access.
 */
enum PluginCapability: string
{
    use HasEnumHelpers;

    case MIDDLEWARE = 'middleware';
    case EVENTS = 'events';
    case FORMS = 'forms';
    case LOGIN = 'login';
    case DONATIONS = 'donations';
    case CLUB_DATA = 'club-data';
    case SCHEDULE = 'schedule';
    case WEBHOOKS = 'webhooks';

    public function description(): string
    {
        return match ($this)
        {
            self::MIDDLEWARE => 'runs on every page request',
            self::EVENTS => 'reacts when things happen, such as events being posted',
            self::FORMS => 'adds fields to core forms and permission requests, and saves them',
            self::LOGIN => 'adds a way to sign in',
            self::DONATIONS => 'adds donations from another system to troopers\' totals',
            self::CLUB_DATA => 'changes club rosters and costumes from outside records',
            self::SCHEDULE => 'runs scheduled tasks',
            self::WEBHOOKS => 'receives webhooks from another system',
        };
    }
}

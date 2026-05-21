<?php

declare(strict_types=1);

namespace App\Enums;

enum FaqSection: string
{
    case REGISTRATION  = 'registration';
    case ACCOUNT_TYPES = 'account-types';
    case ORGANIZATIONS = 'organizations';
    case COSTUMES      = 'costumes';
    case EVENTS        = 'events';
    case SIGNUP        = 'signup';
    case GUESTS        = 'guests';
    case FRIENDS       = 'friends';
    case VIDEOS        = 'videos';

    public function label(): string
    {
        return match ($this)
        {
            self::REGISTRATION  => 'Getting Started & Registration',
            self::ACCOUNT_TYPES => 'Account Types',
            self::ORGANIZATIONS => 'Organizations & Club Memberships',
            self::COSTUMES      => 'Costumes',
            self::EVENTS        => 'Events',
            self::SIGNUP        => 'Signing Up for Events',
            self::GUESTS        => 'Guests',
            self::FRIENDS       => 'Friends',
            self::VIDEOS        => 'How-To Videos',
        };
    }

    public function icon(): string
    {
        return match ($this)
        {
            self::REGISTRATION  => 'fa-user-plus',
            self::ACCOUNT_TYPES => 'fa-id-card',
            self::ORGANIZATIONS => 'fa-sitemap',
            self::COSTUMES      => 'fa-shirt',
            self::EVENTS        => 'fa-calendar',
            self::SIGNUP        => 'fa-clipboard-check',
            self::GUESTS        => 'fa-user-group',
            self::FRIENDS       => 'fa-handshake',
            self::VIDEOS        => 'fa-circle-play',
        };
    }

    public static function toOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case)
        {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}

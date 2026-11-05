<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Plugins\PluginClubSynchronizers;

/**
 * The club synchronizers that ship with TroopTracker.
 */
final class BuiltInClubSynchronizers
{
    /**
     * @var array<string, array{0: string, 1: class-string}>
     */
    public const ALL = [
        '501st' => ['501st Legion', TheLegionService::class],
        'rebel-legion' => ['Rebel Legion', RebelLegionService::class],
        'mandalorian-mercs' => ['Mandalorian Mercs', MandalorianMercsService::class],
        'dark-empire' => ['Dark Empire', DarkEmpireServices::class],
        'droid-builders' => ['Droid Builders', DroidBuildersService::class],
        'saber-guild' => ['Saber Guild', SaberGuildServices::class],
    ];

    public static function register(PluginClubSynchronizers $synchronizers): void
    {
        foreach (self::ALL as $key => [$label, $class])
        {
            $synchronizers->add($key, $label, $class);
        }
    }
}

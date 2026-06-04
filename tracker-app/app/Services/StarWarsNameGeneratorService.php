<?php

declare(strict_types=1);

namespace App\Services;

class StarWarsNameGeneratorService
{
    // Star Wars-sounding name components
    private static array $firstPrefixes = ['Zor', 'Vael', 'Kael', 'Jax', 'Reth', 'Myn', 'Cor', 'Brak', 'Dax', 'Shor', 'Tav', 'Ree'];
    private static array $firstSuffixes = ['in', 'en', 'dan', 'on', 'os', 'or', 'is', 'arek', 'ikas', 'oon', 'eth', 'iv'];

    private static array $lastPrefixes = ['Kel', 'Shan', 'Vorn', 'Droma', 'Ordo', 'Vao', 'Zul', 'Karr', 'Tark', 'Bane', 'Rend'];
    private static array $lastSuffixes = ['rider', 'strider', 'star', 'forge', 'weaver', 'blade', 'runner', 'stone', 'shand'];

    /**
     * Generates a completely random Star Wars name.
     * Example: "Zorrin Kel-Droma" or "Kaelen Vornstar"
     */
    public static function generate(): string
    {
        // Generate First Name
        $firstName = self::$firstPrefixes[array_rand(self::$firstPrefixes)] .
            self::$firstSuffixes[array_rand(self::$firstSuffixes)];

        // Randomly decide between a compound last name (Kel-Droma) or a merged last name (Vornstar)
        if (rand(0, 1) === 0)
        {
            $lastName = self::$lastPrefixes[array_rand(self::$lastPrefixes)] . '-' .
                self::$lastPrefixes[array_rand(self::$lastPrefixes)];
        }
        else
        {
            $lastName = self::$lastPrefixes[array_rand(self::$lastPrefixes)] .
                self::$lastSuffixes[array_rand(self::$lastSuffixes)];
        }

        return ucfirst(strtolower($firstName)) . ' ' . ucfirst(strtolower($lastName));
    }
}

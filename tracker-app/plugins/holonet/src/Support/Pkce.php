<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Support;

/**
 * Proof that the tracker swapping a code is the one that started the link
 * (RFC 7636, S256).
 */
final class Pkce
{
    public static function challenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    public static function verify(string $verifier, string $challenge): bool
    {
        return $verifier !== '' && hash_equals($challenge, self::challenge($verifier));
    }
}

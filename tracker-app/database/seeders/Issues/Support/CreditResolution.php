<?php

declare(strict_types=1);

namespace Database\Seeders\Issues\Support;

/**
 * The credit a row should carry, or why it can't be determined.
 */
readonly class CreditResolution
{
    /** @param  array<int, int>  $org_ids */
    private function __construct(
        public bool $resolved,
        public array $org_ids,
        public string $reason,
    ) {}

    /** @param  array<int, int>  $org_ids */
    public static function resolved(array $org_ids, string $reason): self
    {
        return new self(true, array_values($org_ids), $reason);
    }

    public static function report(string $reason): self
    {
        return new self(false, [], $reason);
    }
}

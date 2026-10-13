<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison\Support;

/**
 * The club credit a TT1.0 signup carries, as recorded on the legacy signup itself.
 */
readonly class LegacyCredit
{
    public const string RESOLVED = 'resolved';

    public const string MISSING = 'missing';

    public const string UNMAPPED = 'unmapped';

    public const string AMBIGUOUS = 'ambiguous';

    /** @param  array<int, int>  $org_ids */
    public function __construct(
        public string $status,
        public array $org_ids,
        public string $note,
    ) {}

    /** @param  array<int, int>  $org_ids */
    public static function resolved(array $org_ids, string $note): self
    {
        return new self(self::RESOLVED, $org_ids, $note);
    }

    public static function missing(): self
    {
        return new self(self::MISSING, [], 'No legacy signup record found for this shift.');
    }

    public static function unmapped(string $note): self
    {
        return new self(self::UNMAPPED, [], $note);
    }

    public static function ambiguous(string $note): self
    {
        return new self(self::AMBIGUOUS, [], $note);
    }

    public function isResolved(): bool
    {
        return $this->status === self::RESOLVED;
    }
}

<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Federation\Messages\Commands;

use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Str;
use TroopTracker\Plugins\Federation\Directory\DirectoryTracker;
use TroopTracker\Plugins\Federation\Models\LinkCode;

/**
 * A one-time code for the tracker a trooper just allowed. Only its hash is kept,
 * and it expires after a few minutes.
 *
 * @method static string call(Trooper $trooper, DirectoryTracker $tracker, string $code_challenge)
 */
final class CreateLinkCode extends Message
{
    public const LIFETIME_MINUTES = 5;

    public function __construct(
        private readonly Trooper $trooper,
        private readonly DirectoryTracker $tracker,
        private readonly string $code_challenge,
    ) {}

    public function handle(): string
    {
        LinkCode::query()->where(LinkCode::EXPIRES_AT, '<', now())->delete();

        $code = Str::random(64);

        LinkCode::query()->create([
            LinkCode::TROOPER_ID => $this->trooper->id,
            LinkCode::TRACKER_URL => $this->tracker->url,
            LinkCode::CODE_HASH => hash('sha256', $code),
            LinkCode::CODE_CHALLENGE => $this->code_challenge,
            LinkCode::EXPIRES_AT => now()->addMinutes(self::LIFETIME_MINUTES),
        ]);

        return $code;
    }
}

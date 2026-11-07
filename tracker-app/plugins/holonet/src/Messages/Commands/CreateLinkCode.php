<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Messages\Commands;

use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Str;
use TroopTracker\Plugins\Holonet\Directory\DirectoryTracker;
use TroopTracker\Plugins\Holonet\Models\HolonetCode;

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
        HolonetCode::query()->where(HolonetCode::EXPIRES_AT, '<', now())->delete();

        $code = Str::random(64);

        HolonetCode::query()->create([
            HolonetCode::TROOPER_ID => $this->trooper->id,
            HolonetCode::TRACKER_URL => $this->tracker->url,
            HolonetCode::CODE_HASH => hash('sha256', $code),
            HolonetCode::CODE_CHALLENGE => $this->code_challenge,
            HolonetCode::EXPIRES_AT => now()->addMinutes(self::LIFETIME_MINUTES),
        ]);

        return $code;
    }
}

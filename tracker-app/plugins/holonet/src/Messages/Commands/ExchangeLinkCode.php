<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Messages\Commands;

use App\Models\Trooper;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;
use Illuminate\Support\Str;
use TroopTracker\Plugins\Holonet\Directory\TrackerDirectory;
use TroopTracker\Plugins\Holonet\Models\HolonetCode;
use TroopTracker\Plugins\Holonet\Models\HolonetGrant;
use TroopTracker\Plugins\Holonet\Support\LinkEligibility;
use TroopTracker\Plugins\Holonet\Support\Pkce;

/**
 * Swaps a one-time code for an access key to the trooper's troop history. The
 * code works once, only for the tracker it was made for, and only with the
 * verifier that tracker started the link with. Returns null when any of that
 * doesn't hold.
 *
 * @method static array{token: string, trooper: Trooper}|null call(string $code, string $code_verifier, string $client)
 */
final class ExchangeLinkCode extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly string $code,
        private readonly string $code_verifier,
        private readonly string $client,
    ) {}

    /**
     * @return array{token: string, trooper: Trooper}|null
     */
    public function handle(TrackerDirectory $directory): ?array
    {
        $link_code = HolonetCode::query()
            ->where(HolonetCode::CODE_HASH, hash('sha256', $this->code))
            ->lockForUpdate()
            ->first();

        if ($link_code === null)
        {
            return null;
        }

        $link_code->delete();

        $tracker = $directory->find($this->client);
        $trooper = Trooper::find($link_code->trooper_id);

        if ($tracker === null
            || $link_code->tracker_url !== $tracker->url
            || $link_code->expires_at->isPast()
            || !Pkce::verify($this->code_verifier, $link_code->code_challenge)
            || $trooper === null
            || !LinkEligibility::canLink($trooper))
        {
            return null;
        }

        $token = Str::random(64);

        HolonetGrant::query()->create([
            HolonetGrant::TROOPER_ID => $trooper->id,
            HolonetGrant::TRACKER_URL => $tracker->url,
            HolonetGrant::TRACKER_NAME => $tracker->name,
            HolonetGrant::TOKEN_HASH => hash('sha256', $token),
        ]);

        return ['token' => $token, 'trooper' => $trooper];
    }
}

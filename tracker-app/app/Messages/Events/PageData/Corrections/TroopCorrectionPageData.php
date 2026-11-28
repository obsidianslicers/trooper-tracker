<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData\Corrections;

use App\Messages\Events\Resources\Corrections\TroopCorrectionReviewResource;
use App\Models\TroopCorrection;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The page a coordinator's "Confirm or Deny" link opens: one request for troop credit.
 *
 * @method static array call(Actor $actor, TroopCorrection $troop_correction)
 */
final class TroopCorrectionPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly TroopCorrection $troop_correction,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $this->troop_correction->loadMissing([
            'trooper',
            'event',
            'event_trooper.event_shift',
            'event_trooper.costume',
        ]);

        return [
            'correction' => new TroopCorrectionReviewResource($this->troop_correction, $this->actor),
        ];
    }
}

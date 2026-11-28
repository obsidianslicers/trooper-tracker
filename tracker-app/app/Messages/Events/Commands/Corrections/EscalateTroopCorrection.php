<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands\Corrections;

use App\Enums\TroopCorrectionStatus;
use App\Jobs\SendTroopVerificationNotificationsJob;
use App\Models\TroopCorrection;
use Hyperdrive\Message;

/**
 * Hands a request for troop credit to the moderators: it joins the Troop Verifications
 * queue, where each club is approved or rejected under the usual coverage rules, and the
 * moderators who cover those clubs are told.
 *
 * @method static void call(TroopCorrection $troop_correction)
 */
final class EscalateTroopCorrection extends Message
{
    public function __construct(private readonly TroopCorrection $troop_correction) {}

    public function handle(): void
    {
        $correction = $this->troop_correction;

        $correction->status = TroopCorrectionStatus::AWAITING_MODERATOR;
        $correction->escalated_at = now();
        $correction->save();

        SendTroopVerificationNotificationsJob::dispatch($correction->event_trooper);
    }
}

<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands\Corrections;

use App\Enums\TroopCorrectionStatus;
use App\Models\TroopCorrection;
use App\Notifications\Events\TroopCorrectionRequestedNotification;
use Hyperdrive\Message;

/**
 * Chases requests for troop credit the event coordinator hasn't answered: one reminder after
 * a week, then the moderators take it over after two (or at once, when the coordinator is
 * gone or no longer active).
 *
 * @method static array{reminded:int,escalated:int} call()
 */
final class FollowUpTroopCorrections extends Message
{
    /**
     * @return array{reminded:int,escalated:int}
     */
    public function handle(): array
    {
        $reminded = 0;
        $escalated = 0;

        foreach ($this->waiting() as $correction)
        {
            $coordinator = $correction->coordinator_trooper;
            $overdue = $correction->created_at->lte(now()->subDays(TroopCorrection::ESCALATE_AFTER_DAYS));

            if ($overdue || $coordinator === null || !$coordinator->is_active)
            {
                EscalateTroopCorrection::call(troop_correction: $correction);
                $escalated++;
            }
            elseif ($correction->reminded_at === null)
            {
                $correction->reminded_at = now();
                $correction->save();

                $coordinator->notify(new TroopCorrectionRequestedNotification($correction, is_reminder: true));
                $reminded++;
            }
        }

        return ['reminded' => $reminded, 'escalated' => $escalated];
    }

    /**
     * @return iterable<int, TroopCorrection>
     */
    private function waiting(): iterable
    {
        return TroopCorrection::query()
            ->where(TroopCorrection::STATUS, TroopCorrectionStatus::AWAITING_COORDINATOR)
            ->where(TroopCorrection::CREATED_AT, '<=', now()->subDays(TroopCorrection::REMIND_AFTER_DAYS))
            ->with(['coordinator_trooper', 'event_trooper'])
            ->get();
    }
}

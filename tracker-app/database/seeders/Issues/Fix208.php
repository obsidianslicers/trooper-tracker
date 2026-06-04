<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Enums\EventGuestStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Models\EventGuest;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Fix208 extends Seeder
{
    private const PLACEHOLDER_ID = 1206;

    public function run(): void
    {
        $placeholder_id = $this->command
            ? (int) $this->command->ask('Placeholder trooper ID', (string) self::PLACEHOLDER_ID)
            : self::PLACEHOLDER_ID;

        $placeholder = Trooper::find($placeholder_id);

        if ($placeholder === null)
        {
            $this->command?->warn("No trooper found with ID {$placeholder_id}.");

            return;
        }

        $administrator = Trooper::where(Trooper::MEMBERSHIP_ROLE, MembershipRole::ADMINISTRATOR)->first();

        if ($administrator === null)
        {
            $this->command?->warn('No administrator trooper found.');

            return;
        }

        DB::transaction(function () use ($placeholder, $administrator): void
        {
            $this->convertSignupsToGuests($placeholder, $administrator);

            $placeholder->delete();
        });
    }

    private function convertSignupsToGuests(Trooper $placeholder, Trooper $administrator): void
    {
        EventTrooper::where(EventTrooper::TROOPER_ID, $placeholder->id)
            ->each(function (EventTrooper $event_trooper) use ($administrator): void
            {
                $count = EventGuest::where(EventGuest::EVENT_SHIFT_ID, $event_trooper->event_shift_id)
                    ->where(EventGuest::NAME, 'like', 'Placeholder%')
                    ->count();

                $name = $count === 0 ? 'Placeholder' : "Placeholder " . ($count + 1);

                EventGuest::create([
                    EventGuest::EVENT_SHIFT_ID => $event_trooper->event_shift_id,
                    EventGuest::ADDED_BY_TROOPER_ID => $administrator->id,
                    EventGuest::NAME => $name,
                    EventGuest::STATUS => $this->mapStatus($event_trooper->status),
                    EventGuest::SIGNED_UP_AT => $event_trooper->signed_up_at,
                ]);

                $event_trooper->delete();
            });
    }

    private function mapStatus(EventTrooperStatus $status): EventGuestStatus
    {
        return match ($status)
        {
            EventTrooperStatus::CANCELLED,
            EventTrooperStatus::NO_SHOW,
            EventTrooperStatus::UNABLE_TO_ATTEND,
            EventTrooperStatus::NOT_PICKED => EventGuestStatus::CANCELLED,
            default => EventGuestStatus::GOING,
        };
    }
}

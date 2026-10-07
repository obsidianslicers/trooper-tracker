<?php

declare(strict_types=1);

namespace Database\Seeders\Issues\Concerns;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Models\EventTrooper;
use Illuminate\Support\Facades\Mail;

/**
 * Collects rows a fix couldn't decide on and emails them to every administrator.
 */
trait ReportsToAdministrators
{
    /** @return array<string, mixed> */
    private function reportRow(EventTrooper $event_trooper, string $reason): array
    {
        return [
            'event_trooper_id' => $event_trooper->id,
            'trooper_name' => $event_trooper->trooper?->display_name ?? "#{$event_trooper->trooper_id}",
            'event_name' => $event_trooper->event_shift?->event?->name ?? 'Unknown event',
            'event_id' => $event_trooper->event_shift?->event?->id,
            'costume_name' => $event_trooper->costume?->name,
            'reason' => $reason,
            'legacy_note' => $reason,
        ];
    }

    /**
     * @param  class-string  $mailable  constructed as new $mailable($admin, $rows)
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function emailAdministrators(MagicBus $bus, string $mailable, array $rows): void
    {
        if (empty($rows))
        {
            return;
        }

        $admins = $bus->send(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));

        foreach ($admins as $admin)
        {
            Mail::to($admin->email)->queue(new $mailable($admin, $rows));
        }
    }
}

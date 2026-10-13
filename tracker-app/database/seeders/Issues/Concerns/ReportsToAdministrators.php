<?php

declare(strict_types=1);

namespace Database\Seeders\Issues\Concerns;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Models\EventTrooper;
use Illuminate\Support\Facades\Mail;

/**
 * Shared console summary and admin email for the credit repair fixes.
 */
trait ReportsToAdministrators
{
    /** @return array<string, mixed> */
    private function reportRow(EventTrooper $event_trooper, string $reason): array
    {
        $trooper_name = $event_trooper->trooper?->display_name ?? "#{$event_trooper->trooper_id}";

        return [
            'event_trooper_id' => $event_trooper->id,
            'trooper_name' => $trooper_name,
            'event_name' => $event_trooper->event_shift?->event?->name ?? 'Unknown event',
            'event_id' => $event_trooper->event_shift?->event?->id,
            'costume_name' => $event_trooper->costume?->name,
            'reason' => $reason,
            'legacy_note' => $reason,
        ];
    }

    /**
     * @param  array<string, string>  $labels  count key => label, in print order
     * @param  array<string, int>  $counts
     */
    private function printCounts(string $title, array $labels, array $counts): void
    {
        $this->command?->info($title);

        foreach ($labels as $key => $label)
        {
            $this->command?->info(sprintf('  %-34s %d', $label.':', $counts[$key] ?? 0));
        }
    }

    private function printRecalculateHint(): void
    {
        $recalculate = 'php artisan tracker:calculate-trooper-achievements --without-notifications';

        $this->command?->newLine();
        $this->command?->info('  Next, recreate earned milestones without re-announcing them:');
        $this->command?->info("  {$recalculate}");
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

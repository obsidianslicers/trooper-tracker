<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SimulateShiftCompleteCommand extends Command
{
    protected $signature = 'tracker:simulate-shift-complete
        {trooper_id : ID of the trooper to generate a test scenario for}
        {--expired : Shift ended more than 30 days ago (outside the update window; default is within window)}
        {--dual-costume : Set up a dual-club costume scenario to trigger the club-selection flow}';

    protected $description = 'Create a test shift-complete scenario for a trooper and output the confirmation URLs. Dev use only.';

    public function handle(): int
    {
        $trooper = Trooper::find($this->argument('trooper_id'));

        if (!$trooper)
        {
            $this->error("No trooper found with ID {$this->argument('trooper_id')}.");

            return self::FAILURE;
        }

        $expired      = $this->option('expired');
        $dual_costume = $this->option('dual-costume');

        $member_orgs = Organization::whereHas('trooper_assignments', fn ($q) =>
            $q->where(TrooperAssignment::TROOPER_ID, $trooper->id)
              ->where(TrooperAssignment::IS_MEMBER, true)
        )->get();

        if ($member_orgs->isEmpty())
        {
            $this->warn("Trooper {$trooper->display_name} has no active club memberships.");
            $this->warn('Creating a standalone org for the event — credit attribution will use default logic.');
            $primary_org = Organization::factory()->create(['name' => 'Test Garrison (auto-created)']);
        }
        else
        {
            $primary_org = $member_orgs->first();
        }

        if ($dual_costume && $member_orgs->count() < 2)
        {
            $this->error('--dual-costume requires the trooper to be a member of at least 2 clubs.');
            $this->error("Found {$member_orgs->count()} active membership(s). Add another TrooperAssignment first.");

            return self::FAILURE;
        }

        // Build the event with the chosen timing scenario.
        $event_end = $expired
            ? Carbon::now()->subDays(45)
            : Carbon::now()->subDay();

        $event = Event::factory()
            ->withOrganization($primary_org)
            ->asClosed()
            ->withEventEnd($event_end)
            ->withEventStart($event_end->clone()->subHours(4))
            ->create();

        // Make the primary org eligible to attend.
        EventOrganization::factory()
            ->forEvent($event)
            ->forOrganization($primary_org)
            ->canAttend()
            ->create();

        $event_shift = EventShift::factory()->forEvent($event)->create([
            EventShift::STATUS        => EventStatus::CLOSED,
            EventShift::SHIFT_ENDS_AT => $event_end,
            EventShift::SHIFT_STARTS_AT => $event_end->clone()->subHours(4),
        ]);

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        if ($dual_costume)
        {
            $credit_orgs = $member_orgs->take(2);

            // Bypass the EventTrooperObserver which recomputes costume_organization_ids
            // based on OrganizationCostume/TrooperCostume records we don't need here.
            DB::table('tt_event_troopers')
                ->where('id', $event_trooper->id)
                ->update(['costume_organization_ids' => json_encode($credit_orgs->pluck('id')->values()->all())]);

            $event_trooper->refresh();
        }

        $attended_token = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);
        $unable_token   = Crypt::encryptString(EventTrooperStatus::UNABLE_TO_ATTEND->value);

        $attended_url = route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status'        => $attended_token,
        ]);

        $unable_url = route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status'        => $unable_token,
        ]);

        $this->newLine();
        $this->line('<fg=cyan;options=bold>── Scenario ──────────────────────────────────────</>');
        $this->info("  Trooper:       {$trooper->display_name} (ID {$trooper->id})");
        $this->info("  Event:         {$event->name} (ID {$event->id})");
        $this->info("  Shift:         {$event_shift->time_display}");
        $this->info("  Event end:     {$event_end->format('Y-m-d H:i')} ({$this->windowLabel($expired)})");
        $this->info("  Updates open:  " . ($event->can_update_trooper_status ? '<fg=green>yes</>' : '<fg=red>no</>'));

        $this->newLine();
        $eligible_orgs = $event_trooper->getEligibleCreditOrganizations();

        if ($eligible_orgs->count() > 1)
        {
            $this->line('<fg=cyan;options=bold>── Club selection will be shown ───────────────────</>');
            foreach ($eligible_orgs as $org)
            {
                $this->info("  [{$org->id}] {$org->name}");
            }
        }
        elseif ($eligible_orgs->count() === 1)
        {
            $this->info('  Eligible clubs: ' . $eligible_orgs->first()->name . ' (single — no selection prompt)');
        }
        else
        {
            $this->info('  Eligible clubs: none (default credit logic applies)');
        }

        $this->newLine();
        $this->line('<fg=cyan;options=bold>── Confirmation URLs ──────────────────────────────</>');
        $this->info('  Attended:');
        $this->line("  {$attended_url}");
        $this->newLine();
        $this->info('  Unable to attend:');
        $this->line("  {$unable_url}");
        $this->newLine();

        return self::SUCCESS;
    }

    private function windowLabel(bool $expired): string
    {
        return $expired ? 'outside 30-day window' : 'within 30-day window';
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Enums\AchievementType;
use App\Enums\EventTrooperStatus;
use App\Features\Troopers\Commands\ExecuteAccountDeletionCommand;
use App\Features\Troopers\Commands\RequestAccountDeletionCommand;
use App\Models\Event;
use App\Models\EventMissionAck;
use App\Models\EventNotification;
use App\Models\EventShare;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventWatch;
use App\Models\MobileDevice;
use App\Models\OauthLogin;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use App\Models\TrooperCostume;
use App\Models\TrooperDonation;
use Database\Factories\Base\EventMissionAckFactory;
use Database\Factories\Base\EventWatchFactory;
use Illuminate\Console\Command;

/**
 * Creates a disposable dummy trooper with associated records to test the
 * account deletion feature — both the UI state (pending banner) and the
 * execution logic (what gets wiped vs. retained). Dev use only.
 */
class SimulateAccountDeletionCommand extends Command
{
    protected $signature = 'tracker:simulate-account-deletion
        {--execute : Skip the 30-day grace period and immediately anonymize and delete the account}';

    protected $description = 'Create a dummy trooper with event history and simulate account deletion. Dev use only.';

    public function handle(MagicBus $bus): int
    {
        $this->newLine();
        $this->line('<fg=yellow;options=bold>⚠  Dev use only — this creates real database records.</>');
        $this->newLine();

        $timestamp = now()->format('Y-m-d H:i:s');

        $trooper = Trooper::factory()
            ->asActive()
            ->withVerifiedEmail()
            ->withPassword('password')
            ->create([
                Trooper::DISPLAY_NAME => "Simulated Trooper ({$timestamp})",
                Trooper::LEGAL_NAME => "Simulated Trooper ({$timestamp})",
            ]);

        $event_shifts = EventShift::inRandomOrder()->take(3)->get();
        $event_trooper_count = $event_shifts->count();

        foreach ($event_shifts as $event_shift)
        {
            EventTrooper::create([
                EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
                EventTrooper::TROOPER_ID => $trooper->id,
                EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
            ]);
        }

        if (!$this->option('execute'))
        {
            return $this->runPreviewMode($bus, $trooper, $event_trooper_count);
        }

        return $this->runExecuteMode($bus, $trooper, $event_trooper_count);
    }

    private function runPreviewMode(MagicBus $bus, Trooper $trooper, int $event_trooper_count): int
    {
        $bus->send(new RequestAccountDeletionCommand($trooper));

        $trooper->refresh();

        $deletion_date = $trooper->deletion_requested_at?->addDays(30)->toFormattedDateString();

        $this->line('<fg=cyan;options=bold>── Account Created ────────────────────────────────</>');
        $this->info("  ID:            {$trooper->id}");
        $this->info("  Display name:  {$trooper->display_name}");
        $this->info("  Email:         {$trooper->email}");
        $this->info('  Password:      password');
        $this->info("  Event sign-ups created: {$event_trooper_count}");

        $this->newLine();
        $this->line('<fg=cyan;options=bold>── Deletion Requested ─────────────────────────────</>');
        $this->info("  Requested at:  {$trooper->deletion_requested_at}");
        $this->info("  Scheduled for: {$deletion_date}");
        $this->warn('  Confirmation email queued (run queue:work to send).');

        $this->newLine();
        $this->line('<fg=cyan;options=bold>── Next Steps ─────────────────────────────────────</>');
        $this->info('  1. Log in with the credentials above: '.route('auth.login'));
        $this->info('  2. Verify the red deletion banner appears with the correct date.');
        $this->info('  3. Click "Cancel Deletion" and confirm the banner disappears.');
        $this->info('  4. Check event rosters to see the trooper listed as signed up.');
        $this->newLine();
        $this->line('  To test the full deletion, re-run with --execute:');
        $this->line('  php artisan tracker:simulate-account-deletion --execute');
        $this->newLine();

        return self::SUCCESS;
    }

    private function runExecuteMode(MagicBus $bus, Trooper $trooper, int $event_trooper_count): int
    {
        $id = $trooper->id;
        $event = Event::inRandomOrder()->first();

        OauthLogin::factory()->forTrooper($trooper)->create();
        OauthLogin::factory()->forTrooper($trooper)->create();

        MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $id]);
        MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $id]);

        $org_costumes = OrganizationCostume::inRandomOrder()->take(2)->get();

        foreach ($org_costumes as $org_costume)
        {
            TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();
        }

        $costume_count = $org_costumes->count();

        TrooperAchievement::factory()->forTrooper($trooper)->withType(AchievementType::TROOPER_RANK)->create();
        TrooperAchievement::factory()->forTrooper($trooper)->withType(AchievementType::TROOPER_SHIFTS)->create();

        TrooperDonation::factory()->forTrooper($trooper)->create();

        EventNotification::factory()->forTrooper($trooper)->forEvent($event)->create();
        EventShare::factory()->forTrooper($trooper)->forEvent($event)->create();
        EventMissionAckFactory::new()->create([EventMissionAck::TROOPER_ID => $id, EventMissionAck::EVENT_ID => $event->id]);
        EventWatchFactory::new()->create([EventWatch::TROOPER_ID => $id, EventWatch::EVENT_ID => $event->id]);

        $this->line('<fg=cyan;options=bold>── Records Created ────────────────────────────────</>');
        $this->table(
            ['Table', 'Count', 'Expected outcome'],
            [
                ['tt_oauth_logins',        2,                    'Hard-deleted'],
                ['tt_mobile_devices',      2,                    'Hard-deleted'],
                ['tt_trooper_costumes',    $costume_count,       'Soft-deleted'],
                ['tt_trooper_achievements', 2,                   'Soft-deleted'],
                ['tt_trooper_donations',   1,                    'Soft-deleted'],
                ['tt_event_troopers',      $event_trooper_count, 'Retained ← verify this'],
                ['tt_event_notifications', 1,                    'Soft-deleted'],
                ['tt_event_shares',        1,                    'Soft-deleted'],
                ['tt_event_mission_acks',  1,                    'Soft-deleted'],
                ['tt_event_watches',       1,                    'Hard-deleted'],
            ]
        );

        $bus->send(new ExecuteAccountDeletionCommand($trooper));

        $fresh = Trooper::withTrashed()->find($id);

        $checks = [
            ['Trooper soft-deleted',    $fresh->deleted_at !== null],
            ['PII anonymized',          $fresh->display_name === 'Deleted Member'],
            ['OAuth logins removed',    OauthLogin::withTrashed()->where(OauthLogin::TROOPER_ID, $id)->count() === 0],
            ['Mobile devices removed',  MobileDevice::where(MobileDevice::TROOPER_ID, $id)->count() === 0],
            ['Costumes soft-deleted',   TrooperCostume::withTrashed()->where(TrooperCostume::TROOPER_ID, $id)->whereNotNull('deleted_at')->count() === $costume_count],
            ['Event sign-ups retained', EventTrooper::where(EventTrooper::TROOPER_ID, $id)->count() === $event_trooper_count],
        ];

        $all_passed = collect($checks)->every(fn (array $c): bool => $c[1]);

        $this->newLine();
        $this->line('<fg=cyan;options=bold>── Verification ───────────────────────────────────</>');
        $this->table(
            ['Check', 'Result'],
            array_map(fn (array $c): array => [$c[0], $c[1] ? '<fg=green>✓ pass</>' : '<fg=red>✗ fail</>'], $checks)
        );

        if ($all_passed)
        {
            $this->info('  All checks passed.');
        }
        else
        {
            $this->error('  One or more checks failed — review the deletion handler.');
        }

        $this->newLine();
        $this->line('<fg=cyan;options=bold>── Roster Appearance ──────────────────────────────</>');
        $this->info("  Event sign-ups for trooper ID {$id} now show:");
        $this->info("  Display name: {$fresh->display_name}");
        $this->info("  Email:        {$fresh->email}");
        $this->newLine();

        return $all_passed ? self::SUCCESS : self::FAILURE;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessAccountDeletionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymizes_trooper_past_grace_period(): void
    {
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::DELETION_REQUESTED_AT => now()->subDays(31),
        ]);

        $this->artisan('tracker:process-account-deletions')->assertExitCode(0);

        $this->assertSoftDeleted('tt_troopers', [Trooper::ID => $trooper->id]);
    }

    public function test_does_not_process_trooper_within_grace_period(): void
    {
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::DELETION_REQUESTED_AT => now()->subDays(15),
        ]);

        $this->artisan('tracker:process-account-deletions')->assertExitCode(0);

        $this->assertNull($trooper->fresh()->deleted_at);
    }

    public function test_does_not_process_trooper_without_deletion_request(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $this->artisan('tracker:process-account-deletions')->assertExitCode(0);

        $this->assertNull($trooper->fresh()->deleted_at);
    }

    public function test_outputs_count_of_processed_deletions(): void
    {
        Trooper::factory()->asActive()->create([
            Trooper::DELETION_REQUESTED_AT => now()->subDays(31),
        ]);

        $this->artisan('tracker:process-account-deletions')
            ->expectsOutput('Processed 1 account deletion(s).')
            ->assertExitCode(0);
    }
}

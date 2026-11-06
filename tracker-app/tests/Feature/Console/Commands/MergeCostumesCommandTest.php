<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Messages\Costumes\Commands\Merge\MergeCostumes;
use App\Messages\Costumes\Queries\GetCostumeUsage;
use App\Models\Costume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class MergeCostumesCommandTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    private const array USAGE = ['organizations' => 1, 'troopers' => 2, 'event_signups' => 3];

    public function test_merges_source_into_target_after_confirmation(): void
    {
        $source = Costume::factory()->create([Costume::NAME => 'TK Stormtroper']);
        $target = Costume::factory()->create([Costume::NAME => 'TK Stormtrooper']);

        $messages = $this->fakeMessages([
            GetCostumeUsage::class => self::USAGE,
            MergeCostumes::class => null,
        ]);

        $this->artisan('tracker:merge-costumes', [
            'source_costume_id' => $source->id,
            'target_costume_id' => $target->id,
        ])
            ->expectsConfirmation(
                'Merge "TK Stormtroper" into "TK Stormtrooper"? '
                    .'"TK Stormtroper" will be permanently deleted.',
                'yes',
            )
            ->expectsOutput('Merged "TK Stormtroper" into "TK Stormtrooper".')
            ->assertExitCode(0);

        $messages->assertCalled(
            MergeCostumes::class,
            fn (array $params): bool => $params['source_costume']->id === $source->id
                && $params['target_costume']->id === $target->id,
        );
    }

    public function test_does_not_merge_when_confirmation_is_declined(): void
    {
        $source = Costume::factory()->create();
        $target = Costume::factory()->create();

        $messages = $this->fakeMessages([
            GetCostumeUsage::class => self::USAGE,
            MergeCostumes::class => null,
        ]);

        $this->artisan('tracker:merge-costumes', [
            'source_costume_id' => $source->id,
            'target_costume_id' => $target->id,
        ])
            ->expectsConfirmation(
                "Merge \"{$source->name}\" into \"{$target->name}\"? "
                    ."\"{$source->name}\" will be permanently deleted.",
                'no',
            )
            ->expectsOutput('Merge cancelled.')
            ->assertExitCode(0);

        $this->assertSame([], $messages->callsTo(MergeCostumes::class));
    }

    public function test_force_skips_confirmation(): void
    {
        $source = Costume::factory()->create();
        $target = Costume::factory()->create();

        $messages = $this->fakeMessages([
            GetCostumeUsage::class => self::USAGE,
            MergeCostumes::class => null,
        ]);

        $this->artisan('tracker:merge-costumes', [
            'source_costume_id' => $source->id,
            'target_costume_id' => $target->id,
            '--force' => true,
        ])->assertExitCode(0);

        $messages->assertCalled(MergeCostumes::class);
    }

    public function test_fails_when_source_and_target_are_the_same(): void
    {
        $costume = Costume::factory()->create();

        $messages = $this->fakeMessages([MergeCostumes::class => null]);

        $this->artisan('tracker:merge-costumes', [
            'source_costume_id' => $costume->id,
            'target_costume_id' => $costume->id,
        ])
            ->expectsOutput('source_costume_id and target_costume_id must be different costumes.')
            ->assertExitCode(1);

        $this->assertSame([], $messages->callsTo(MergeCostumes::class));
    }

    public function test_fails_when_a_costume_is_missing(): void
    {
        $target = Costume::factory()->create();

        $messages = $this->fakeMessages([MergeCostumes::class => null]);

        $this->artisan('tracker:merge-costumes', [
            'source_costume_id' => 999999,
            'target_costume_id' => $target->id,
        ])
            ->expectsOutput('Source costume 999999 was not found.')
            ->assertExitCode(1);

        $this->assertSame([], $messages->callsTo(MergeCostumes::class));
    }

    public function test_fails_when_target_is_soft_deleted(): void
    {
        $source = Costume::factory()->create();
        $target = Costume::factory()->create();
        $target->delete();

        $this->artisan('tracker:merge-costumes', [
            'source_costume_id' => $source->id,
            'target_costume_id' => $target->id,
        ])
            ->expectsOutput("Target costume {$target->id} was not found.")
            ->assertExitCode(1);
    }
}

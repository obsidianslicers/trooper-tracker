<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Messages\Events\Commands\DeleteEventUpload;
use App\Messages\Events\Queries\GetEventUploadsMissingFiles;
use App\Models\EventUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class PruneMissingUploadsCommandTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_lists_missing_uploads_without_deleting_by_default(): void
    {
        $upload = EventUpload::factory()->create();
        $messages = $this->fakeMessages([
            GetEventUploadsMissingFiles::class => collect([$upload]),
            DeleteEventUpload::class => null,
        ]);

        $this->artisan('tracker:prune-missing-uploads')
            ->expectsOutputToContain('1 photo record(s) point at missing files.')
            ->assertExitCode(0);

        $messages->assertNotCalled(DeleteEventUpload::class);
    }

    public function test_deletes_missing_uploads_after_confirmation(): void
    {
        $upload = EventUpload::factory()->create();
        $messages = $this->fakeMessages([
            GetEventUploadsMissingFiles::class => collect([$upload]),
            DeleteEventUpload::class => null,
        ]);

        $this->artisan('tracker:prune-missing-uploads', ['--delete' => true])
            ->expectsConfirmation('Delete 1 photo record(s)?', 'yes')
            ->expectsOutput('Deleted 1 photo record(s).')
            ->assertExitCode(0);

        $messages->assertCalled(
            DeleteEventUpload::class,
            fn (array $params): bool => $params['event_upload']->id === $upload->id,
        );
    }

    public function test_does_not_delete_when_confirmation_is_declined(): void
    {
        $messages = $this->fakeMessages([
            GetEventUploadsMissingFiles::class => collect([EventUpload::factory()->create()]),
            DeleteEventUpload::class => null,
        ]);

        $this->artisan('tracker:prune-missing-uploads', ['--delete' => true])
            ->expectsConfirmation('Delete 1 photo record(s)?', 'no')
            ->expectsOutput('Nothing deleted.')
            ->assertExitCode(0);

        $messages->assertNotCalled(DeleteEventUpload::class);
    }

    public function test_force_skips_the_confirmation(): void
    {
        $messages = $this->fakeMessages([
            GetEventUploadsMissingFiles::class => collect([EventUpload::factory()->create()]),
            DeleteEventUpload::class => null,
        ]);

        $this->artisan('tracker:prune-missing-uploads', ['--delete' => true, '--force' => true])
            ->expectsOutput('Deleted 1 photo record(s).')
            ->assertExitCode(0);

        $messages->assertCalled(DeleteEventUpload::class);
    }

    public function test_reports_when_nothing_is_missing(): void
    {
        $this->fakeMessages([GetEventUploadsMissingFiles::class => collect()]);

        $this->artisan('tracker:prune-missing-uploads')
            ->expectsOutput('Every event photo has its file on disk.')
            ->assertExitCode(0);
    }
}

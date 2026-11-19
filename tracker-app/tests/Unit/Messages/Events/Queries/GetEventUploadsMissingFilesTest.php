<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Queries;

use App\Messages\Events\Queries\GetEventUploadsMissingFiles;
use App\Models\EventUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GetEventUploadsMissingFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('legacy_uploads');
    }

    public function test_handle_returns_uploads_whose_storage_file_is_missing(): void
    {
        Storage::disk('public')->put('uploads/events/1/originals/kept.jpg', 'x');
        $kept = $this->upload('uploads/events/1/originals/kept.jpg');
        $missing = $this->upload('uploads/events/1/originals/gone.jpg');

        $result = (new GetEventUploadsMissingFiles)->handle();

        $this->assertSame([$missing->id], $result->pluck(EventUpload::ID)->all());
        $this->assertFalse($result->contains($kept));
    }

    public function test_handle_checks_legacy_filenames_in_the_legacy_folder(): void
    {
        Storage::disk('legacy_uploads')->put('2019-old.jpeg', 'x');
        $this->upload('2019-old.jpeg');
        $missing = $this->upload('2019-gone.jpeg');

        $result = (new GetEventUploadsMissingFiles)->handle();

        $this->assertSame([$missing->id], $result->pluck(EventUpload::ID)->all());
    }

    public function test_handle_returns_empty_when_every_file_exists(): void
    {
        Storage::disk('public')->put('uploads/events/1/originals/kept.jpg', 'x');
        $this->upload('uploads/events/1/originals/kept.jpg');

        $result = (new GetEventUploadsMissingFiles)->handle();

        $this->assertTrue($result->isEmpty());
    }

    private function upload(string $path): EventUpload
    {
        return EventUpload::factory()->create([
            EventUpload::IMAGE_PATH_LG => $path,
            EventUpload::IMAGE_PATH_SM => $path,
        ]);
    }
}

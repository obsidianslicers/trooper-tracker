<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Api\PageData;

use App\Messages\Api\PageData\PublicPhotosPageData;
use App\Models\EventUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicPhotosPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_photos_whose_files_exist_up_to_the_amount(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/events/1/originals/a.jpg', 'x');
        Storage::disk('public')->put('uploads/events/1/originals/b.jpg', 'x');
        $this->photo('uploads/events/1/originals/a.jpg');
        $this->photo('uploads/events/1/originals/b.jpg');
        $this->photo('uploads/events/1/originals/gone.jpg');

        $subject = new PublicPhotosPageData(amount: 1);
        $result = $subject->handle();

        $this->assertCount(1, $result);
        $this->assertSame(['fileName', 'imageUrl', 'thumbnailUrl', 'troopID', 'trooperID'], array_keys($result[0]));
        $this->assertStringNotContainsString('gone.jpg', $result[0]['fileName']);
    }

    public function test_handle_leaves_out_administrative_uploads(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/events/1/originals/a.jpg', 'x');
        $this->photo('uploads/events/1/originals/a.jpg', administrative: true);

        $subject = new PublicPhotosPageData(amount: 5);

        $this->assertSame([], $subject->handle());
    }

    private function photo(string $path, bool $administrative = false): EventUpload
    {
        return EventUpload::factory()->create([
            EventUpload::IMAGE_PATH_LG => $path,
            EventUpload::IMAGE_PATH_SM => $path,
            EventUpload::IS_ADMINISTRATIVE => $administrative,
        ]);
    }
}

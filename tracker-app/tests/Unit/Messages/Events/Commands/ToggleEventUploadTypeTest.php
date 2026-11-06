<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Messages\Events\Commands\ToggleEventUploadType;
use App\Models\EventUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToggleEventUploadTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_moves_mission_review_upload_to_admin_uploads(): void
    {
        $upload = EventUpload::factory()->create([
            EventUpload::IS_ADMINISTRATIVE => false,
        ]);

        $result = (new ToggleEventUploadType($upload))->handle();

        $this->assertTrue($result->is_administrative);
        $this->assertTrue($upload->refresh()->is_administrative);
    }

    public function test_handle_moves_admin_upload_to_mission_review(): void
    {
        $upload = EventUpload::factory()->create([
            EventUpload::IS_ADMINISTRATIVE => true,
        ]);

        $result = (new ToggleEventUploadType($upload))->handle();

        $this->assertFalse($result->is_administrative);
        $this->assertFalse($upload->refresh()->is_administrative);
    }
}

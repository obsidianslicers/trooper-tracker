<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\ToggleEventUploadTypeCommand;
use App\Features\Events\Commands\ToggleEventUploadTypeCommandHandler;
use App\Models\EventUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToggleEventUploadTypeCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private ToggleEventUploadTypeCommandHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = app(ToggleEventUploadTypeCommandHandler::class);
    }

    public function test_invoke_moves_mission_review_upload_to_admin_uploads(): void
    {
        $upload = EventUpload::factory()->create([
            EventUpload::IS_ADMINISTRATIVE => false,
        ]);

        $result = ($this->subject)(new ToggleEventUploadTypeCommand($upload));

        $this->assertTrue($result->is_administrative);
        $this->assertTrue($upload->refresh()->is_administrative);
    }

    public function test_invoke_moves_admin_upload_to_mission_review(): void
    {
        $upload = EventUpload::factory()->create([
            EventUpload::IS_ADMINISTRATIVE => true,
        ]);

        $result = ($this->subject)(new ToggleEventUploadTypeCommand($upload));

        $this->assertFalse($result->is_administrative);
        $this->assertFalse($upload->refresh()->is_administrative);
    }
}

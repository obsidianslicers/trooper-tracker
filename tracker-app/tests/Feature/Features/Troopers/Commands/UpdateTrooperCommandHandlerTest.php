<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\UpdateTrooperCommand;
use App\Features\Troopers\Commands\UpdateTrooperCommandHandler;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see UpdateTrooperCommandHandler
 */
class UpdateTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_with_valid_data(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::DISPLAY_NAME => 'Old Name',
            Trooper::PHONE => '555-0000',
        ]);

        $valid_data = [
            'display_name' => 'New Name',
            'phone' => '404-555-9999',
        ];

        $command = new UpdateTrooperCommand(
            trooper: $trooper,
            valid_data: $valid_data,
            complete_setup: false
        );
        $handler = app(UpdateTrooperCommandHandler::class);

        $handler($command);

        $trooper->refresh();
        $this->assertEquals('New Name', $trooper->display_name);
        $this->assertEquals('404-555-9999', $trooper->phone);
    }

    public function test_invoke_marks_setup_complete_when_flag_is_true(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::SETUP_COMPLETED_AT => null,
        ]);

        $this->assertNull($trooper->setup_completed_at);

        $command = new UpdateTrooperCommand(
            trooper: $trooper,
            valid_data: ['display_name' => 'Test'],
            complete_setup: true
        );
        $handler = app(UpdateTrooperCommandHandler::class);

        $handler($command);

        $trooper->refresh();
        $this->assertNotNull($trooper->setup_completed_at);
    }

    public function test_invoke_does_not_mark_setup_complete_when_flag_is_false(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::SETUP_COMPLETED_AT => null,
        ]);

        $command = new UpdateTrooperCommand(
            trooper: $trooper,
            valid_data: ['display_name' => 'Test'],
            complete_setup: false
        );
        $handler = app(UpdateTrooperCommandHandler::class);

        $handler($command);

        $trooper->refresh();
        $this->assertNull($trooper->setup_completed_at);
    }
}

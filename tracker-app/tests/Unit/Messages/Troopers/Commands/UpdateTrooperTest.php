<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Messages\Troopers\Commands\UpdateTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_updates_trooper_with_valid_data(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::DISPLAY_NAME => 'Old Name',
            Trooper::PHONE => '555-0000',
        ]);

        $valid_data = [
            'display_name' => 'New Name',
            'phone' => '404-555-9999',
        ];

        $command = new UpdateTrooper(
            trooper: $trooper,
            valid_data: $valid_data,
            complete_setup: false
        );

        $command->handle();

        $trooper->refresh();
        $this->assertEquals('New Name', $trooper->display_name);
        $this->assertEquals('404-555-9999', $trooper->phone);
    }

    public function test_handle_marks_setup_complete_when_flag_is_true(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::SETUP_COMPLETED_AT => null,
        ]);

        $this->assertNull($trooper->setup_completed_at);

        $command = new UpdateTrooper(
            trooper: $trooper,
            valid_data: ['display_name' => 'Test'],
            complete_setup: true
        );

        $command->handle();

        $trooper->refresh();
        $this->assertNotNull($trooper->setup_completed_at);
    }

    public function test_handle_does_not_mark_setup_complete_when_flag_is_false(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::SETUP_COMPLETED_AT => null,
        ]);

        $command = new UpdateTrooper(
            trooper: $trooper,
            valid_data: ['display_name' => 'Test'],
            complete_setup: false
        );

        $command->handle();

        $trooper->refresh();
        $this->assertNull($trooper->setup_completed_at);
    }
}

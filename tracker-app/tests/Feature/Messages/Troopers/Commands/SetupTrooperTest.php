<?php

declare(strict_types=1);

namespace Tests\Feature\Messages\Troopers\Commands;

use App\Enums\NotificationFrequency;
use App\Enums\TrooperTheme;
use App\Models\Trooper;
use App\Messages\Troopers\Commands\SetupTrooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_saves_trooper_setup_details_and_marks_setup_complete(): void
    {
        $trooper = Trooper::factory()->withSetupIncomplete()->create();
        $theme = TrooperTheme::REBEL;
        $notification_frequency = NotificationFrequency::DAILY;

        $subject = new SetupTrooper(
            trooper: $trooper,
            email: 'rebel@example.com',
            legal_name: 'Leia Organa',
            theme: $theme,
            notification_frequency: $notification_frequency,
        );

        $subject->handle();

        $updated_trooper = $trooper->fresh();

        $this->assertSame('rebel@example.com', $updated_trooper->email);
        $this->assertSame('Leia Organa', $updated_trooper->legal_name);
        $this->assertSame($theme, $updated_trooper->theme);
        $this->assertSame(
            $notification_frequency,
            $updated_trooper->notification_frequency
        );
        $this->assertNotNull($updated_trooper->setup_completed_at);
    }
}

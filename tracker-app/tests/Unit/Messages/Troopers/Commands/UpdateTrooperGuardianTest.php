<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Messages\Troopers\Commands\UpdateTrooperGuardian;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTrooperGuardianTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_links_the_trooper_with_the_guardian_email(): void
    {
        $guardian = Trooper::factory()->create();
        $trooper = Trooper::factory()->create();

        $subject = new UpdateTrooperGuardian($trooper, ['guardian_email' => $guardian->email]);
        $subject->handle();

        $this->assertSame($guardian->id, $trooper->refresh()->guardian_id);
    }

    public function test_handle_clears_the_guardian_when_the_email_is_empty(): void
    {
        $guardian = Trooper::factory()->create();
        $trooper = Trooper::factory()->withGuardian($guardian)->create();

        $subject = new UpdateTrooperGuardian($trooper, ['guardian_email' => null]);
        $subject->handle();

        $this->assertNull($trooper->refresh()->guardian_id);
    }

    public function test_handle_keeps_the_guardian_when_no_email_is_given(): void
    {
        $guardian = Trooper::factory()->create();
        $trooper = Trooper::factory()->withGuardian($guardian)->create();

        $subject = new UpdateTrooperGuardian($trooper, [Trooper::DATE_OF_BIRTH => null]);
        $subject->handle();

        $this->assertSame($guardian->id, $trooper->refresh()->guardian_id);
    }
}

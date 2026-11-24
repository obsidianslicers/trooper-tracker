<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords\Pages;

use App\Messages\Troopers\Queries\GetCommandStaff;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class ShowCommandStaffControllerTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('service-records.command-staff'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_renders_command_staff_view(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();

        $command_staff = collect([
            Trooper::factory()->asSystemOwner()->withDisplayName('Alpha Admin')->create(),
            Trooper::factory()->asModerator()->withDisplayName('Bravo Moderator')->create(),
        ]);

        $messages = $this->fakeMessages([
            GetCommandStaff::class => $command_staff,
        ]);

        $response = $this->actingAs($trooper)->get(route('service-records.command-staff'));

        $messages->assertCalled(GetCommandStaff::class);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('service-records/CommandStaff')
            ->where('troopers.0.display_name', 'Alpha Admin')
            ->where('troopers.0.role', 'System Owner')
            ->where('troopers.1.display_name', 'Bravo Moderator'));
    }
}

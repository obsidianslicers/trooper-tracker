<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords;

use App\Messages\Troopers\Queries\GetCommandStaff;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class CommandStaffControllerTest extends TestCase
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
            Trooper::factory()->asAdministrator()->withDisplayName('Alpha Admin')->create(),
            Trooper::factory()->asModerator()->withDisplayName('Bravo Moderator')->create(),
        ]);

        $messages = $this->fakeMessages([
            GetCommandStaff::class => $command_staff,
        ]);

        $response = $this->actingAs($trooper)->get(route('service-records.command-staff'));

        $messages->assertCalled(GetCommandStaff::class);

        $response->assertOk();
        $response->assertViewIs('pages.service-records.command-staff');
        $response->assertViewHas('troopers', function (Collection $result): bool
        {
            return $result->pluck(Trooper::DISPLAY_NAME)->values()->all() === [
                'Alpha Admin',
                'Bravo Moderator',
            ];
        });
    }
}

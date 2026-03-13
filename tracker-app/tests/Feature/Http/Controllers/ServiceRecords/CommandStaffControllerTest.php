<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords;

use App\Bus\MagicBus;
use App\Features\Troopers\Queries\GetCommandStaffQuery;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class CommandStaffControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('service-records.command-staff'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_renders_command_staff_view(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $command_staff = collect([
            Trooper::factory()->asAdministrator()->withDisplayName('Alpha Admin')->create(),
            Trooper::factory()->asModerator()->withDisplayName('Bravo Moderator')->create(),
        ]);

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($command_staff): void
        {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (GetCommandStaffQuery $query): bool
                {
                    return $query instanceof GetCommandStaffQuery;
                })
                ->andReturn($command_staff);
        });

        $response = $this->actingAs($trooper)->get(route('service-records.command-staff'));

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

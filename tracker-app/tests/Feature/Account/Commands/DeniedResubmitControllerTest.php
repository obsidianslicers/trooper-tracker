<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Commands;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeniedResubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_denied_resubmit_controller_calls_command(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();
        $organization = Organization::factory()->create();

        $response = $this->actingAs($trooper)->post(route('account.denied.resubmit'), [
            'organizations' => [
                $organization->id => ['selected' => '1'],
            ],
        ]);

        $response->assertRedirect(route('account.pending'));
    }

    public function test_denied_resubmit_flashes_success_message(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();
        $organization = Organization::factory()->create();

        $response = $this->actingAs($trooper)->post(route('account.denied.resubmit'), [
            'organizations' => [
                $organization->id => ['selected' => '1'],
            ],
        ]);

        $response->assertSessionHas('success');
    }
}

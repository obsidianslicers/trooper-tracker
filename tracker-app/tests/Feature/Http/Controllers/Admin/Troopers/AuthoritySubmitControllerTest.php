<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthoritySubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_authority_and_redirects_to_list(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $payload = [
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MODERATOR->value,
            'organizations' => [
                (string) $organization->id => [
                    TrooperAssignment::IS_MODERATOR => true,
                ],
            ],
        ];

        $response = $this->actingAs($trooper)->post(route('admin.troopers.authority', $subject), $payload);

        $response->assertRedirect(route('admin.troopers.list'));

        $this->assertDatabaseHas('tt_troopers', [
            Trooper::ID => $subject->id,
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MODERATOR->value,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $subject = Trooper::factory()->create();

        $payload = [
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MODERATOR->value,
            'organizations' => [],
        ];

        $response = $this->post(route('admin.troopers.authority', $subject), $payload);

        $response->assertRedirect(route('auth.login'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_profile_and_redirects_to_list(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $subject = Trooper::factory()->create();

        $payload = [
            Trooper::LEGAL_NAME => 'TK-11111 New Legal Name',
            Trooper::DISPLAY_NAME => 'TK New Display',
            Trooper::EMAIL => 'trooper.updated@example.com',
            Trooper::PHONE => '(555) 123-4567',
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
        ];

        $response = $this->actingAs($trooper)->post(route('admin.troopers.profile', $subject), $payload);

        $response->assertRedirect(route('admin.troopers.list'));

        $subject->refresh();

        $this->assertSame('TK-11111 New Legal Name', $subject->{Trooper::LEGAL_NAME});
        $this->assertSame('TK New Display', $subject->{Trooper::DISPLAY_NAME});
        $this->assertSame('trooper.updated@example.com', $subject->{Trooper::EMAIL});
        $this->assertSame('555-123-4567', $subject->{Trooper::PHONE});
    }

    public function test_invoke_requires_authentication(): void
    {
        $subject = Trooper::factory()->create();

        $payload = [
            Trooper::LEGAL_NAME => 'Legal Name',
            Trooper::DISPLAY_NAME => 'Display Name',
            Trooper::EMAIL => 'trooper@example.com',
        ];

        $response = $this->post(route('admin.troopers.profile', $subject), $payload);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_refuses_a_moderator_marking_a_trooper_departed(): void
    {
        $organization = Organization::factory()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $subject = Trooper::factory()->asMember()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($organization)
            ->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($subject)->forOrganization($organization)
            ->asMember()->create();

        $response = $this->actingAs($moderator)->post(route('admin.troopers.profile', $subject), [
            Trooper::LEGAL_NAME => $subject->legal_name,
            Trooper::DISPLAY_NAME => $subject->display_name,
            Trooper::EMAIL => 'moderated@example.com',
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::DEPARTED->value,
        ]);

        $response->assertSessionHasErrors(Trooper::MEMBERSHIP_STATUS);
        $this->assertNotSame(MembershipStatus::DEPARTED, $subject->refresh()->membership_status);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Troopers\Pages;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_trooper_profile_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $subject = Trooper::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.troopers.profile', $subject));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/troopers/Profile')
            ->where('trooper_id', $subject->id));
    }

    public function test_invoke_offers_void_and_rip_to_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $subject = Trooper::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.troopers.profile', $subject));

        $response->assertInertia(fn ($page) => $page
            ->where('details.can_void', true)
            ->where('details.can_unvoid', false)
            ->where('details.can_mark_rip', true)
            ->where('details.can_unmark_rip', false));
    }

    public function test_invoke_offers_restore_for_voided_trooper(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $subject = Trooper::factory()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::INVALID,
        ]);

        $response = $this->actingAs($trooper)->get(route('admin.troopers.profile', $subject));

        $response->assertInertia(fn ($page) => $page
            ->where('details.can_void', false)
            ->where('details.can_unvoid', true));
    }

    public function test_invoke_requires_authentication(): void
    {
        $subject = Trooper::factory()->create();

        $response = $this->get(route('admin.troopers.profile', $subject));

        $response->assertRedirect(route('auth.login'));
    }
}

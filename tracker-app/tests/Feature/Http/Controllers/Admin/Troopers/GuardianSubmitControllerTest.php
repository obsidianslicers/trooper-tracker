<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GuardianSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_date_of_birth_and_sets_guardian_id(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $guardian = Trooper::factory()->create();
        $trooper = Trooper::factory()->create();
        $dob = Carbon::now()->subYears(20)->toDateString();

        $response = $this->actingAs($admin)->post(route('admin.troopers.guardian', $trooper), [
            Trooper::DATE_OF_BIRTH => $dob,
            'guardian_email'       => $guardian->email,
        ]);

        $this->assertDatabaseHas('tt_troopers', [
            Trooper::ID          => $trooper->id,
            Trooper::GUARDIAN_ID => $guardian->id,
            Trooper::DATE_OF_BIRTH => $dob . ' 00:00:00',
        ]);
    }

    public function test_invoke_clears_guardian_and_dob_when_both_null(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $guardian = Trooper::factory()->create();
        $trooper = Trooper::factory()->withGuardian($guardian)->create([
            Trooper::DATE_OF_BIRTH => Carbon::now()->subYears(20)->toDateString(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.troopers.guardian', $trooper), [
            Trooper::DATE_OF_BIRTH => null,
            'guardian_email'       => null,
        ]);

        $this->assertDatabaseHas('tt_troopers', [
            Trooper::ID          => $trooper->id,
            Trooper::GUARDIAN_ID => null,
            Trooper::DATE_OF_BIRTH => null,
        ]);
    }

    public function test_invoke_redirects_to_guardian_route_after_success(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.troopers.guardian', $trooper), [
            Trooper::DATE_OF_BIRTH => null,
            'guardian_email'       => null,
        ]);

        $response->assertRedirect(route('admin.troopers.guardian', $trooper));
    }

    public function test_invoke_requires_guardian_email_when_dob_makes_trooper_a_minor(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->create();
        $dob = Carbon::now()->subYears(16)->toDateString();

        $response = $this->actingAs($admin)->post(route('admin.troopers.guardian', $trooper), [
            Trooper::DATE_OF_BIRTH => $dob,
        ]);

        $response->assertSessionHasErrors('guardian_email');
    }

    public function test_invoke_rejects_guardian_email_not_in_troopers_table(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->create();
        $dob = Carbon::now()->subYears(20)->toDateString();

        $response = $this->actingAs($admin)->post(route('admin.troopers.guardian', $trooper), [
            Trooper::DATE_OF_BIRTH => $dob,
            'guardian_email'       => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors('guardian_email');
    }

    public function test_invoke_requires_authentication(): void
    {
        $trooper = Trooper::factory()->create();

        $response = $this->post(route('admin.troopers.guardian', $trooper));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_forbids_member_role(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $trooper = Trooper::factory()->create();

        $response = $this->actingAs($member)->post(route('admin.troopers.guardian', $trooper), [
            Trooper::DATE_OF_BIRTH => null,
            'guardian_email'       => null,
        ]);

        $response->assertForbidden();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_profile_and_redirects_to_list(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
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
        $this->assertSame('5551234567', $subject->{Trooper::PHONE});
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
}

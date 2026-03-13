<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_notices_page_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $notice = Notice::factory()
            ->forOrganization($organization)
            ->create();

        $response = $this->actingAs($trooper)->get(route('account.notices'));

        $response->assertOk();
        $response->assertViewIs('pages.account.notices');
        $response->assertViewHas('notices');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('account.notices'));

        $response->assertRedirect(route('auth.login'));
    }
}

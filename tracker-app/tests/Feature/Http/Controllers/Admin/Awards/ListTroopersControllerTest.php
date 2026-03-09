<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTroopersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_award_trooper_list_for_admin(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $recipient = Trooper::factory()->asMember()->create();
        $award = Award::factory()->create();

        AwardTrooper::factory()->forAward($award)->forTrooper($recipient)->create();

        $response = $this->actingAs($admin)->get(route('admin.awards.list-troopers', ['award' => $award->id]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.awards.list-troopers');
    }

    public function test_invoke_requires_authentication(): void
    {
        $award = Award::factory()->create();

        $response = $this->get(route('admin.awards.list-troopers', ['award' => $award->id]));

        $response->assertRedirect(route('auth.login'));
    }
}

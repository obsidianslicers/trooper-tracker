<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards\Pages;

use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IndexAwardTroopersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_award_trooper_list_for_admin(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $recipient = Trooper::factory()->asMember()->create();
        $award = Award::factory()->create();

        AwardTrooper::factory()->forAward($award)->forTrooper($recipient)->create();

        $response = $this->actingAs($admin)->get(route('admin.awards.troopers.index', ['award' => $award->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/awards/Troopers')
            ->where('award.id', $award->id)
            ->has('troopers', 1)
            ->where('troopers.0.display_name', $recipient->display_name));
    }

    public function test_invoke_requires_authentication(): void
    {
        $award = Award::factory()->create();

        $response = $this->get(route('admin.awards.troopers.index', ['award' => $award->id]));

        $response->assertRedirect(route('auth.login'));
    }
}

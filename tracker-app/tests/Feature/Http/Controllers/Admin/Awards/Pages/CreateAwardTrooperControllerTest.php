<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards\Pages;

use App\Models\Award;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreateAwardTrooperControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_assign_trooper_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $award = Award::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.awards.troopers.create', ['award' => $award->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/awards/AssignTrooper')
            ->where('award.id', $award->id)
            ->where('award_date', $award->frequency->normalizeDate(now())->format('Y-m-d')));
    }

    public function test_invoke_requires_authentication(): void
    {
        $award = Award::factory()->create();

        $response = $this->get(route('admin.awards.troopers.create', ['award' => $award->id]));

        $response->assertRedirect(route('auth.login'));
    }
}

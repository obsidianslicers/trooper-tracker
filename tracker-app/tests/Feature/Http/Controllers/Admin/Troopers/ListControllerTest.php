<?php

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

<<<<<<< HEAD
use App\Enums\MembershipRole;
=======
>>>>>>> b60e060 (feature: add notice board)
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

<<<<<<< HEAD
    protected function setUp(): void
=======
    public function test_invoke_redirects_unauthenticated_users(): void
>>>>>>> b60e060 (feature: add notice board)
    {
        parent::setUp();
    }

<<<<<<< HEAD
    public function test_invoke_as_admin_returns_all_troopers(): void
    {
        // Arrange
        $admin_user = Trooper::factory()->asAdmin()->create();
        Trooper::factory()->count(5)->create();
=======
    public function test_invoke_forbidden_for_unauthorized_users(): void
    {
        // Arrange
        $unauthorized_user = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($unauthorized_user)
            ->get(route('admin.troopers.list'));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_as_admin_shows_all_troopers_paginated(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdmin()->create();
        Trooper::factory()->count(20)->create();
>>>>>>> b60e060 (feature: add notice board)

        // Act
        $response = $this->actingAs($admin_user)->get(route('admin.troopers.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.list');
<<<<<<< HEAD
        $response->assertViewHas('troopers', function ($troopers)
        {
            return $troopers->count() === 6;
=======
        $response->assertViewHas('troopers', function (LengthAwarePaginator $troopers)
        {
            return $troopers->total() === 21 && $troopers->count() === 15; // 20 created + 1 admin
>>>>>>> b60e060 (feature: add notice board)
        });
    }

    public function test_invoke_as_moderator_returns_only_moderated_troopers(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
<<<<<<< HEAD
        $moderator_user = Trooper::factory()->asModerator()->withAssignment($unit, moderator: true)->create();
        Trooper::factory()->count(3)->create(); // Unassigned troopers
        Trooper::factory()->count(2)->withAssignment($unit, member: true)->create();
=======
        $region = $unit->parent;
        $unit2 = Organization::factory()->unit()->create();

        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($region, moderator: true)
            ->create();

        // This trooper is in a unit under the moderator's region
        $moderated_trooper = Trooper::factory()
            ->asMember()
            ->withAssignment($unit)
            ->create();

        // This trooper is in a different unit/region
        $unrelated_trooper = Trooper::factory()
            ->asMember()
            ->withAssignment($unit2)
            ->create();
>>>>>>> b60e060 (feature: add notice board)

        // Act
        $response = $this->actingAs($moderator_user)->get(route('admin.troopers.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.list');
<<<<<<< HEAD
        $response->assertViewHas('troopers', function ($troopers)
        {
            return $troopers->count() === 3;
        });
=======
        $response->assertViewHas('troopers', function (LengthAwarePaginator $troopers) use ($moderator, $moderated_trooper)
        {
            // Moderator sees themself and the trooper they moderate
            return $troopers->total() === 2
                && $troopers->contains($moderator)
                && $troopers->contains($moderated_trooper);
        });

        $response->assertViewHas('troopers', function (LengthAwarePaginator $troopers) use ($unrelated_trooper)
        {
            // Moderator should NOT see the unrelated trooper
            return !$troopers->contains($unrelated_trooper);
        });
    }

    public function test_invoke_returns_empty_collection_when_no_troopers_exist(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdmin()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('troopers', fn(LengthAwarePaginator $p) => $p->total() === 1);
>>>>>>> b60e060 (feature: add notice board)
    }

    public function test_invoke_with_search_term_filters_by_name(): void
    {
        // Arrange
        $admin_user = Trooper::factory()->asAdmin()->create();
        Trooper::factory()->create(['name' => 'John Doe']);
        Trooper::factory()->create(['name' => 'Jane Smith']);

        // Act
        $response = $this->actingAs($admin_user)
            ->get(route('admin.troopers.list', ['search_term' => 'John']));

        // Assert
        $response->assertOk();
        $response->assertViewHas('troopers', function ($troopers)
        {
            return $troopers->count() === 1 && $troopers->first()->name === 'John Doe';
        });
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith');
    }

    public function test_invoke_with_search_term_filters_by_email(): void
    {
        // Arrange
        $admin_user = Trooper::factory()->asAdmin()->create();
        Trooper::factory()->create(['email' => 'test@example.com']);
        Trooper::factory()->create(['email' => 'another@example.com']);

        // Act
        $response = $this->actingAs($admin_user)
            ->get(route('admin.troopers.list', ['search_term' => 'test@']));

        // Assert
        $response->assertOk();
        $response->assertViewHas('troopers', function ($troopers)
        {
            return $troopers->count() === 1 && $troopers->first()->email === 'test@example.com';
        });
    }

    public function test_invoke_with_search_term_filters_by_username(): void
    {
        // Arrange
        $admin_user = Trooper::factory()->asAdmin()->create();
        Trooper::factory()->create(['username' => 'TK12345']);
        Trooper::factory()->create(['username' => 'DZ54321']);

        // Act
        $response = $this->actingAs($admin_user)
            ->get(route('admin.troopers.list', ['search_term' => 'TK12']));

        // Assert
        $response->assertOk();
        $response->assertViewHas('troopers', function ($troopers)
        {
            return $troopers->count() === 1 && $troopers->first()->username === 'TK12345';
        });
    }

    public function test_invoke_with_membership_role_filter(): void
    {
        // Arrange
        $admin_user = Trooper::factory()->asAdmin()->create();
        Trooper::factory()->asModerator()->create();
        Trooper::factory()->asMember()->create();

        // Act
        $response = $this->actingAs($admin_user)
            ->get(route('admin.troopers.list', ['membership_role' => MembershipRole::ADMINISTRATOR->value]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('troopers', function ($troopers)
        {
            $all_admin = true;
            foreach ($troopers as $trooper)
            {
                if ($trooper->membership_role !== MembershipRole::ADMINISTRATOR)
                {
                    $all_admin = false;
                    break;
                }
            }
            return $troopers->count() === 1 && $all_admin;
        });
    }

    public function test_invoke_with_search_and_role_filters_as_moderator(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $moderator_user = Trooper::factory()->asModerator()->withAssignment($unit, moderator: true)->create();

        Trooper::factory()->withAssignment($unit, member: true)->asModerator()->create([
            'name' => 'Matching Trooper',
        ]);
        Trooper::factory()->withAssignment($unit, member: true)->create([
            'name' => 'Matching Name',
            'membership_role' => MembershipRole::MEMBER,
        ]);

        Trooper::factory()->asActive()->create([
            'name' => 'Matching Unmoderated',
        ]);

        // Act
        $response = $this->actingAs($moderator_user)->get(route('admin.troopers.list', [
            'search_term' => 'Matching',
            'membership_role' => MembershipRole::MODERATOR->value,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('troopers', function ($troopers)
        {
            return $troopers->count() === 1 && $troopers->first()->name === 'Matching Trooper';
        });
        $response->assertSee('Matching Trooper');
        $response->assertDontSee('Matching Name');
        $response->assertDontSee('Matching Unmoderated');
    }
}
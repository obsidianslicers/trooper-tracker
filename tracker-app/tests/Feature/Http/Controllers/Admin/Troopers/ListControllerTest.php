<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_redirects_unauthenticated_users(): void
    {
        // Arrange
        // No user is authenticated

        // Act
        $response = $this->get(route('admin.troopers.list'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

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

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.troopers.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.list');
        $response->assertViewHas('troopers', function (LengthAwarePaginator $troopers)
        {
            return $troopers->total() === 21 && $troopers->count() === 15; // 20 created + 1 admin
        });
    }

    public function test_invoke_as_moderator_shows_only_moderated_troopers(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
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

        // Act
        $response = $this->actingAs($moderator)
            ->get(route('admin.troopers.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.list');
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
    }
}

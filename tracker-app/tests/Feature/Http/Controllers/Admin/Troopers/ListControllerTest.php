<?php

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_invoke_as_admin_returns_all_troopers(): void
    {
        // Arrange
        $admin_user = Trooper::factory()->asAdmin()->create();
        Trooper::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($admin_user)->get(route('admin.troopers.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.list');
        $response->assertViewHas('troopers', function ($troopers)
        {
            return $troopers->count() === 6;
        });
    }

    public function test_invoke_as_moderator_returns_only_moderated_troopers(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $moderator_user = Trooper::factory()->asModerator()->withAssignment($unit, moderator: true)->create();
        Trooper::factory()->count(3)->create(); // Unassigned troopers
        Trooper::factory()->count(2)->withAssignment($unit, member: true)->create();

        // Act
        $response = $this->actingAs($moderator_user)->get(route('admin.troopers.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.list');
        $response->assertViewHas('troopers', function ($troopers)
        {
            return $troopers->count() === 3;
        });
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
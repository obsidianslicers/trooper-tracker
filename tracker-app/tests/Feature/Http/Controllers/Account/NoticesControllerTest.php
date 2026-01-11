<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Notice;
use App\Models\NoticeTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for NoticesController.
 *
 * Verifies:
 * - Authenticated troopers can view their notices page
 * - Only notices visible to the trooper are displayed
 * - Unread notices are properly filtered
 * - Global notices are visible to all troopers
 * - Organization-scoped notices respect hierarchy
 * - Notices are ordered by start date
 * - Unauthenticated users are redirected to login
 */
class NoticesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_notices_page(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('pages.account.notices');
    }

    public function test_invoke_passes_notices_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null, // Global notice
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertViewHas('notices');
    }

    public function test_invoke_displays_global_notices_to_all_troopers(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $global_notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null, // Global notice
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertViewHas('notices', function ($notices) use ($global_notice)
        {
            return $notices->contains('id', $global_notice->id);
        });
    }

    public function test_invoke_displays_organization_notices_to_members(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => $organization->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertViewHas('notices', function ($notices) use ($notice)
        {
            return $notices->contains('id', $notice->id);
        });
    }

    public function test_invoke_hides_organization_notices_from_non_members(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Trooper is NOT a member of the organization

        $notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => $organization->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertViewHas('notices', function ($notices) use ($notice)
        {
            return !$notices->contains('id', $notice->id);
        });
    }

    public function test_invoke_only_displays_unread_notices(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $unread_notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null,
        ]);

        $read_notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null,
        ]);

        // Mark second notice as read
        NoticeTrooper::create([
            NoticeTrooper::NOTICE_ID => $read_notice->id,
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::IS_READ => true,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertViewHas('notices', function ($notices) use ($unread_notice, $read_notice)
        {
            return $notices->contains('id', $unread_notice->id)
                && !$notices->contains('id', $read_notice->id);
        });
    }

    public function test_invoke_orders_notices_by_start_date(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $notice1 = Notice::factory()->create([
            Notice::ORGANIZATION_ID => null,
            Notice::STARTS_AT => now()->addDays(3),
            Notice::ENDS_AT => now()->addDays(10),
        ]);

        $notice2 = Notice::factory()->create([
            Notice::ORGANIZATION_ID => null,
            Notice::STARTS_AT => now()->addDays(1),
            Notice::ENDS_AT => now()->addDays(10),
        ]);

        $notice3 = Notice::factory()->create([
            Notice::ORGANIZATION_ID => null,
            Notice::STARTS_AT => now()->addDays(2),
            Notice::ENDS_AT => now()->addDays(10),
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertViewHas('notices', function ($notices) use ($notice1, $notice2, $notice3)
        {
            return $notices->pluck('id')->toArray() === [
                $notice2->id, // Day 1
                $notice3->id, // Day 2
                $notice1->id, // Day 3
            ];
        });
    }

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('account.notices'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_multiple_global_notices(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $notice1 = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null,
        ]);
        $notice2 = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null,
        ]);
        $notice3 = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertViewHas('notices', function ($notices) use ($notice1, $notice2, $notice3)
        {
            return $notices->count() === 3
                && $notices->contains('id', $notice1->id)
                && $notices->contains('id', $notice2->id)
                && $notices->contains('id', $notice3->id);
        });
    }

    public function test_invoke_displays_mix_of_global_and_organization_notices(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $global_notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null,
        ]);

        $org_notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => $organization->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertViewHas('notices', function ($notices) use ($global_notice, $org_notice)
        {
            return $notices->count() === 2
                && $notices->contains('id', $global_notice->id)
                && $notices->contains('id', $org_notice->id);
        });
    }

    public function test_invoke_isolates_notices_per_trooper(): void
    {
        // Arrange
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();

        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper1->id,
            TrooperAssignment::ORGANIZATION_ID => $organization1->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper2->id,
            TrooperAssignment::ORGANIZATION_ID => $organization2->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $notice1 = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => $organization1->id,
        ]);

        $notice2 = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => $organization2->id,
        ]);

        // Act
        $response1 = $this->actingAs($trooper1)
            ->get(route('account.notices'));

        $response2 = $this->actingAs($trooper2)
            ->get(route('account.notices'));

        // Assert - trooper1 sees only org1 notices
        $response1->assertViewHas('notices', function ($notices) use ($notice1, $notice2)
        {
            return $notices->contains('id', $notice1->id)
                && !$notices->contains('id', $notice2->id);
        });

        // Assert - trooper2 sees only org2 notices
        $response2->assertViewHas('notices', function ($notices) use ($notice1, $notice2)
        {
            return !$notices->contains('id', $notice1->id)
                && $notices->contains('id', $notice2->id);
        });
    }

    public function test_invoke_shows_empty_notices_when_none_available(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertViewHas('notices', function ($notices)
        {
            return $notices->isEmpty();
        });
    }

    public function test_invoke_includes_notice_marked_as_unread(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null,
        ]);

        // Mark as unread explicitly
        NoticeTrooper::create([
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::IS_READ => false,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertViewHas('notices', function ($notices) use ($notice)
        {
            return $notices->contains('id', $notice->id);
        });
    }

    public function test_invoke_handles_active_notices(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $active_notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertViewHas('notices', function ($notices) use ($active_notice)
        {
            return $notices->contains('id', $active_notice->id);
        });
    }

    public function test_invoke_handles_future_notices(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $future_notice = Notice::factory()->future()->create([
            Notice::ORGANIZATION_ID => null,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertViewHas('notices', function ($notices) use ($future_notice)
        {
            return $notices->contains('id', $future_notice->id);
        });
    }

    public function test_invoke_includes_past_notices(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $past_notice = Notice::factory()->past()->create([
            Notice::ORGANIZATION_ID => null,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertViewHas('notices', function ($notices) use ($past_notice)
        {
            return $notices->contains('id', $past_notice->id);
        });
    }

    public function test_invoke_returns_view_response(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.notices'));

        // Assert
        $response->assertOk();
    }
}

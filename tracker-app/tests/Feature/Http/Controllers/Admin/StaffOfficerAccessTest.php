<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffOfficerAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function developerRoutes(): array
    {
        return [
            'plugins' => ['admin.plugins.list'],
            'system check' => ['admin.system-check'],
            'merge troopers' => ['admin.troopers.merge'],
            'merge costumes' => ['admin.costumes.merge'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function administratorRoutes(): array
    {
        return [
            'command staff' => ['admin.display'],
            'permission approvals' => ['admin.troopers.permissions.approvals'],
            'faq' => ['admin.faq.index'],
            'costumes' => ['admin.costumes.list'],
        ];
    }

    #[DataProvider('developerRoutes')]
    public function test_staff_officer_cannot_open_developer_tools(string $route): void
    {
        $staff_officer = Trooper::factory()->asStaffOfficer()->create();

        $response = $this->actingAs($staff_officer)->get(route($route));

        $response->assertForbidden();
    }

    #[DataProvider('administratorRoutes')]
    public function test_staff_officer_can_open_administrator_pages(string $route): void
    {
        $staff_officer = Trooper::factory()->asStaffOfficer()->create();

        $response = $this->actingAs($staff_officer)->get(route($route));

        $response->assertOk();
    }

    public function test_staff_officer_cannot_submit_merge_troopers(): void
    {
        $staff_officer = Trooper::factory()->asStaffOfficer()->create();
        $source = Trooper::factory()->create();
        $target = Trooper::factory()->create();

        $response = $this->actingAs($staff_officer)->post(route('admin.troopers.merge'), [
            'source_trooper_id' => $source->id,
            'target_trooper_id' => $target->id,
        ]);

        $response->assertForbidden();
    }

    public function test_dashboard_hides_developer_tools_from_staff_officer(): void
    {
        $staff_officer = Trooper::factory()->asStaffOfficer()->create();

        $response = $this->actingAs($staff_officer)->get(route('admin.display'));

        $response->assertOk();
        $response->assertSee('Permission Approvals');
        $response->assertSee('FAQ &amp; Help', false);
        $response->assertDontSee('System Check');
        $response->assertDontSee('Enable, Disable, and Configure Plugins');
        $response->assertDontSee('Merge Troopers');
        $response->assertDontSee('Merge Costumes');
    }

    public function test_dashboard_shows_developer_tools_to_administrator(): void
    {
        $administrator = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($administrator)->get(route('admin.display'));

        $response->assertSee('System Check');
        $response->assertSee('Merge Troopers');
        $response->assertSee('Merge Costumes');
    }

    public function test_staff_officer_can_make_a_trooper_a_staff_officer(): void
    {
        $staff_officer = Trooper::factory()->asStaffOfficer()->create();
        $subject = Trooper::factory()->create();

        $response = $this->actingAs($staff_officer)->post(route('admin.troopers.update-authority', $subject), [
            Trooper::MEMBERSHIP_ROLE => MembershipRole::STAFF_OFFICER->value,
            'organizations' => [],
        ]);

        $response->assertOk();
        $this->assertSame(MembershipRole::STAFF_OFFICER, $subject->fresh()->membership_role);
    }

    public function test_staff_officer_cannot_make_a_trooper_an_administrator(): void
    {
        $staff_officer = Trooper::factory()->asStaffOfficer()->create();
        $subject = Trooper::factory()->create();

        $response = $this->actingAs($staff_officer)->post(route('admin.troopers.update-authority', $subject), [
            Trooper::MEMBERSHIP_ROLE => MembershipRole::SYSTEM_OWNER->value,
            'organizations' => [],
        ]);

        $response->assertSessionHasErrors(Trooper::MEMBERSHIP_ROLE);
        $this->assertSame(MembershipRole::MEMBER, $subject->fresh()->membership_role);
    }

    public function test_staff_officer_cannot_change_an_administrator(): void
    {
        $staff_officer = Trooper::factory()->asStaffOfficer()->create();
        $administrator = Trooper::factory()->asSystemOwner()->create();

        $this->actingAs($staff_officer)
            ->get(route('admin.troopers.profile', $administrator))
            ->assertInertia(fn ($page) => $page->where('authority', null));

        $this->actingAs($staff_officer)
            ->post(route('admin.troopers.update-authority', $administrator), [
                Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER->value,
                'organizations' => [],
            ])
            ->assertForbidden();

        $this->assertSame(MembershipRole::SYSTEM_OWNER, $administrator->fresh()->membership_role);
    }

    public function test_authority_page_hides_administrator_option_from_staff_officer(): void
    {
        $staff_officer = Trooper::factory()->asStaffOfficer()->create();
        $subject = Trooper::factory()->create();

        $response = $this->actingAs($staff_officer)
            ->get(route('admin.troopers.profile', $subject));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/troopers/Profile')
            ->where('authority.role_options', function ($options): bool
            {
                $values = collect($options)->pluck('value')->all();

                return in_array('staff_officer', $values, true)
                    && !in_array('system_owner', $values, true);
            }));
    }

    public function test_staff_officer_cannot_make_themselves_an_administrator(): void
    {
        $staff_officer = Trooper::factory()->asStaffOfficer()->create();

        $response = $this->actingAs($staff_officer)
            ->post(route('admin.troopers.update-authority', $staff_officer), [
                Trooper::MEMBERSHIP_ROLE => MembershipRole::SYSTEM_OWNER->value,
                'organizations' => [],
            ]);

        $response->assertSessionHasErrors(Trooper::MEMBERSHIP_ROLE);
        $this->assertSame(
            MembershipRole::STAFF_OFFICER,
            $staff_officer->fresh()->membership_role
        );
    }

    public function test_staff_officer_cannot_change_an_administrators_profile(): void
    {
        $staff_officer = Trooper::factory()->asStaffOfficer()->create();
        $administrator = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($staff_officer)
            ->post(route('admin.troopers.update-profile', $administrator), [
                Trooper::LEGAL_NAME => 'Taken Over',
                Trooper::DISPLAY_NAME => 'Taken Over',
                Trooper::EMAIL => 'staff@example.com',
                Trooper::MEMBERSHIP_STATUS => MembershipStatus::RETIRED->value,
            ]);

        $response->assertForbidden();
        $this->assertSame(MembershipStatus::ACTIVE, $administrator->fresh()->membership_status);
    }

    public function test_staff_officer_cannot_void_an_administrator(): void
    {
        $staff_officer = Trooper::factory()->asStaffOfficer()->create();
        $administrator = Trooper::factory()->asSystemOwner()->create();

        $this->actingAs($staff_officer)
            ->post(route('admin.troopers.void', $administrator))
            ->assertForbidden();

        $this->assertSame(MembershipStatus::ACTIVE, $administrator->fresh()->membership_status);
    }

    public function test_authority_roles_are_listed_by_rank(): void
    {
        $administrator = Trooper::factory()->asSystemOwner()->create();
        $subject = Trooper::factory()->create();

        $response = $this->actingAs($administrator)
            ->get(route('admin.troopers.profile', $subject));

        $response->assertInertia(fn ($page) => $page
            ->where('authority.role_options.0.value', 'member')
            ->where('authority.role_options.4.value', 'moderator')
            ->where('authority.role_options.6.value', 'system_owner'));
    }
}

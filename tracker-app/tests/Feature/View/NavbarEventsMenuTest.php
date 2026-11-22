<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The Events menu offers Create Event to everyone who may create events, so command staff
 * don't have to go through the Command Staff dashboard to start one.
 */
class NavbarEventsMenuTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function eventCreators(): array
    {
        return [
            'system owner' => ['asSystemOwner'],
            'staff officer' => ['asStaffOfficer'],
            'moderator' => ['asModerator'],
            'event coordinator' => ['asEventCoordinator'],
        ];
    }

    #[DataProvider('eventCreators')]
    public function test_everyone_who_can_create_events_gets_create_event(string $role): void
    {
        $trooper = Trooper::factory()->{$role}()->create();

        $this->actingAs($trooper)
            ->get(route('faq'))
            ->assertOk()
            ->assertSee(route('admin.events.create'), false);
    }

    public function test_command_staff_do_not_get_my_events(): void
    {
        $this->actingAs(Trooper::factory()->asModerator()->create())
            ->get(route('faq'))
            ->assertOk()
            ->assertDontSee(route('admin.events.index').'"', false);
    }

    public function test_members_do_not_get_create_event(): void
    {
        $this->actingAs(Trooper::factory()->asMember()->create())
            ->get(route('faq'))
            ->assertOk()
            ->assertDontSee(route('admin.events.create'), false);
    }
}

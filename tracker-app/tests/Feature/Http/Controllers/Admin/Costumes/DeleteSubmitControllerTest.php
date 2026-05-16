<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Costumes;

use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeleteSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_soft_deletes_costume_and_redirects_to_list(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $costume = Costume::factory()->create();

        $response = $this->actingAs($admin)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post("/admin/costumes/{$costume->id}/delete");

        $response->assertRedirect(route('admin.costumes.list'));
        $this->assertSoftDeleted('tt_costumes', ['id' => $costume->id]);
    }

    public function test_invoke_soft_deletes_organization_and_trooper_costumes(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->create();
        $organization = Organization::factory()->create();

        $org_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->create();

        $trooper_costume = TrooperCostume::factory()
            ->forTrooper($member)
            ->forOrganizationCostume($org_costume)
            ->create();

        $this->actingAs($admin)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post("/admin/costumes/{$costume->id}/delete");

        $this->assertSoftDeleted('tt_organization_costumes', ['id' => $org_costume->id]);
        $this->assertSoftDeleted('tt_trooper_costumes', ['id' => $trooper_costume->id]);
    }

    public function test_invoke_nulls_costume_id_on_event_trooper_while_preserving_credit(): void
    {
        $this->skipIfSqlite();

        $admin = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($member)
            ->create();

        // Directly set values to bypass the observer and simulate a trooper
        // who had a costume with credited organizations before deletion.
        DB::table('tt_event_troopers')->where('id', $event_trooper->id)->update([
            'costume_id' => $costume->id,
            'costume_organization_ids' => json_encode([1, 2]),
        ]);

        $this->actingAs($admin)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post("/admin/costumes/{$costume->id}/delete");

        $this->assertDatabaseHas('tt_event_troopers', [
            'id' => $event_trooper->id,
            'costume_id' => null,
        ]);

        $record = DB::table('tt_event_troopers')->where('id', $event_trooper->id)->first();
        $this->assertSame([1, 2], json_decode($record->costume_organization_ids, true));
    }

    public function test_invoke_nulls_backup_costume_id_on_event_trooper(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($member)
            ->create();

        DB::table('tt_event_troopers')->where('id', $event_trooper->id)->update([
            'backup_costume_id' => $costume->id,
        ]);

        $this->actingAs($admin)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post("/admin/costumes/{$costume->id}/delete");

        $this->assertDatabaseHas('tt_event_troopers', [
            'id' => $event_trooper->id,
            'backup_costume_id' => null,
        ]);
    }

    public function test_invoke_prevents_deletion_of_handler_costume(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $costume = Costume::factory()->withName(Costume::HANDLER)->create();

        $response = $this->actingAs($admin)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post("/admin/costumes/{$costume->id}/delete");

        $response->assertForbidden();
        $this->assertDatabaseHas('tt_costumes', ['id' => $costume->id, 'deleted_at' => null]);
    }

    public function test_invoke_prevents_deletion_of_command_staff_costume(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $costume = Costume::factory()->withName(Costume::COMMAND_STAFF)->create();

        $response = $this->actingAs($admin)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post("/admin/costumes/{$costume->id}/delete");

        $response->assertForbidden();
        $this->assertDatabaseHas('tt_costumes', ['id' => $costume->id, 'deleted_at' => null]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $costume = Costume::factory()->create();

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post("/admin/costumes/{$costume->id}/delete");

        $response->assertRedirect(route('auth.login'));
    }
}

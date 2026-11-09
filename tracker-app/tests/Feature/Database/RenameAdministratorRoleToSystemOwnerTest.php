<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Enums\MembershipRole;
use App\Models\ModelChange;
use App\Models\Trooper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RenameAdministratorRoleToSystemOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_up_renames_stored_administrator_roles(): void
    {
        $owner = Trooper::factory()->asSystemOwner()->create();
        $member = Trooper::factory()->create();
        $this->setRole($owner, 'administrator');
        $change = $this->createRoleChange($member, 'administrator', 'member');

        $this->migration()->up();

        $this->assertSame(MembershipRole::SYSTEM_OWNER, $owner->fresh()->membership_role);
        $this->assertSame(MembershipRole::MEMBER, $member->fresh()->membership_role);
        $this->assertSame('system_owner', $change->fresh()->old_value);
        $this->assertSame('member', $change->fresh()->new_value);
    }

    public function test_down_restores_administrator(): void
    {
        $owner = Trooper::factory()->asSystemOwner()->create();
        $change = $this->createRoleChange($owner, 'member', 'system_owner');

        $this->migration()->down();

        $this->assertSame('administrator', $this->storedRole($owner));
        $this->assertSame('administrator', $change->fresh()->new_value);
    }

    private function migration(): Migration
    {
        return require database_path(
            'migrations/2026_11_09_000001_rename_administrator_role_to_system_owner.php'
        );
    }

    private function setRole(Trooper $trooper, string $role): void
    {
        DB::table('tt_troopers')->where('id', $trooper->id)->update(['membership_role' => $role]);
    }

    private function storedRole(Trooper $trooper): string
    {
        return DB::table('tt_troopers')->where('id', $trooper->id)->value('membership_role');
    }

    private function createRoleChange(Trooper $trooper, string $old, string $new): ModelChange
    {
        return ModelChange::create([
            ModelChange::AUDITABLE_TYPE => Trooper::class,
            ModelChange::AUDITABLE_ID => $trooper->id,
            ModelChange::FIELD_NAME => 'membership_role',
            ModelChange::OLD_VALUE => $old,
            ModelChange::NEW_VALUE => $new,
        ]);
    }
}

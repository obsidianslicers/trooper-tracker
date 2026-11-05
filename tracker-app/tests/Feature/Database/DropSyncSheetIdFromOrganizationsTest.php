<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class DropSyncSheetIdFromOrganizationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject()->down();
    }

    public function test_up_drops_the_column_when_it_holds_no_sheets(): void
    {
        $organization = Organization::factory()->create();
        $this->setOldSheet($organization, ' ');

        $this->subject()->up();

        $this->assertFalse(Schema::hasColumn('tt_organizations', 'sync_sheet_id'));
    }

    public function test_up_stops_when_the_plugin_never_copied_the_sheets(): void
    {
        $this->setOldSheet(Organization::factory()->create(), 'sheet-1');

        try
        {
            $this->subject()->up();
            $this->fail('The migration should have stopped.');
        }
        catch (RuntimeException $e)
        {
            $this->assertStringContainsString('plugins:enable google-sheets', $e->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('tt_organizations', 'sync_sheet_id'));
    }

    public function test_up_copies_sheets_the_plugin_table_is_missing_then_drops(): void
    {
        Schema::create('tt_organization_google_sheets', function (Blueprint $table): void
        {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('sheet_id', 128);
            $table->timestamps();
        });
        $copied = Organization::factory()->create();
        $missing = Organization::factory()->create();
        $this->setOldSheet($copied, 'old');
        $this->setOldSheet($missing, ' sheet-2 ');
        DB::table('tt_organization_google_sheets')
            ->insert(['organization_id' => $copied->id, 'sheet_id' => 'kept']);

        $this->subject()->up();

        $this->assertSame(
            ['kept', 'sheet-2'],
            DB::table('tt_organization_google_sheets')->orderBy('organization_id')
                ->pluck('sheet_id')->all(),
        );
        $this->assertFalse(Schema::hasColumn('tt_organizations', 'sync_sheet_id'));
    }

    private function setOldSheet(Organization $organization, string $sheet_id): void
    {
        DB::table('tt_organizations')->where('id', $organization->id)
            ->update(['sync_sheet_id' => $sheet_id]);
    }

    private function subject(): Migration
    {
        return require database_path(
            'migrations/2026_11_06_000002_drop_sync_sheet_id_from_organizations.php'
        );
    }
}

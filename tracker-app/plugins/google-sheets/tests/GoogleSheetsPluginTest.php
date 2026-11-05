<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\GoogleSheets\Tests;

use App\Enums\SystemCheckStatus;
use App\Exceptions\SheetUnavailableException;
use App\Models\Organization;
use App\Models\Trooper;
use App\Plugins\PluginManager;
use App\Plugins\PluginSheetSource;
use App\Plugins\PluginSystemChecks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;
use TroopTracker\Plugins\GoogleSheets\Database\Seeders\ClubSheetsSeeder;
use TroopTracker\Plugins\GoogleSheets\GoogleSheetSource;
use TroopTracker\Plugins\GoogleSheets\Models\OrganizationSheet;
use TroopTracker\Plugins\GoogleSheets\Services\GoogleSheetsClient;

class GoogleSheetsPluginTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $plugins;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugins = app(PluginManager::class);
    }

    public function test_enabling_copies_sheet_ids_and_leaves_the_core_column_alone(): void
    {
        $with_sheet = $this->organizationWithOldSheet(' sheet-1 ');
        $empty = $this->organizationWithOldSheet('');

        $this->enablePlugin();

        $this->assertSame('sheet-1', OrganizationSheet::forOrganization($with_sheet->id));
        $this->assertNull(OrganizationSheet::forOrganization($empty->id));
        $this->assertSame(1, OrganizationSheet::query()->count());
        $this->assertSame(
            ' sheet-1 ',
            DB::table('tt_organizations')->where('id', $with_sheet->id)->value('sync_sheet_id'),
        );
    }

    public function test_enabling_sets_the_sheet_source(): void
    {
        $this->assertNull(app(PluginSheetSource::class)->get());

        $this->enablePlugin();

        $this->assertInstanceOf(GoogleSheetSource::class, app(PluginSheetSource::class)->get());
    }

    public function test_source_reads_the_saved_sheet(): void
    {
        $this->enablePlugin();
        $organization = Organization::factory()->create();
        OrganizationSheet::store($organization->id, 'sheet-9');
        $client = Mockery::mock(GoogleSheetsClient::class);
        $client->shouldReceive('getSheet')->once()->with('sheet-9', 'Troopers')->andReturn([['ID']]);
        $subject = new GoogleSheetSource($client);

        $this->assertSame([['ID']], $subject->rows($organization, 'Troopers'));
    }

    public function test_source_returns_null_without_a_saved_sheet(): void
    {
        $this->enablePlugin();
        $client = Mockery::mock(GoogleSheetsClient::class);
        $client->shouldNotReceive('getSheet');
        $subject = new GoogleSheetSource($client);

        $this->assertNull($subject->rows(Organization::factory()->create(), 'Troopers'));
    }

    public function test_client_without_credentials_reports_the_sheet_unavailable(): void
    {
        $subject = new GoogleSheetsClient('/nowhere/google-credentials.json');

        $this->assertFalse($subject->isConfigured());
        $this->expectException(SheetUnavailableException::class);

        $subject->getSheet('sheet-1', 'Troopers');
    }

    public function test_credentials_path_setting_picks_the_file(): void
    {
        $this->enablePlugin();
        $path = tempnam(sys_get_temp_dir(), 'creds');
        $this->plugins->updateSettings('google-sheets', ['credentials_path' => $path]);

        $this->assertTrue(app(GoogleSheetsClient::class)->isConfigured());

        $this->plugins->updateSettings('google-sheets', ['credentials_path' => 'missing.json']);

        $this->assertFalse(app(GoogleSheetsClient::class)->isConfigured());
        unlink($path);
    }

    public function test_system_check_reports_the_credentials(): void
    {
        $this->enablePlugin();
        $this->plugins->updateSettings('google-sheets', ['credentials_path' => 'missing.json']);

        $results = app(PluginSystemChecks::class)->run()->keyBy('label');

        $this->assertSame(SystemCheckStatus::WARN, $results['Google Sheets credentials']->status);
    }

    public function test_organization_form_shows_and_saves_the_sheet_for_clubs(): void
    {
        $this->enablePlugin();
        $admin = Trooper::factory()->asAdministrator()->create();
        $club = Organization::factory()->asOrganization()->create();
        OrganizationSheet::store($club->id, 'sheet-old');

        $this->actingAs($admin)
            ->get(route('admin.organizations.update', ['organization' => $club]))
            ->assertOk()
            ->assertSee('Google Sync Sheet ID:')
            ->assertSee('sheet-old');

        $this->actingAs($admin)->post(
            route('admin.organizations.update', ['organization' => $club]),
            [Organization::NAME => $club->name, 'google_sheet_id' => 'sheet-new'],
        )->assertRedirect(route('admin.organizations.list'));

        $this->assertSame('sheet-new', OrganizationSheet::forOrganization($club->id));
    }

    public function test_organization_form_has_no_sheet_field_for_regions(): void
    {
        $this->enablePlugin();
        $admin = Trooper::factory()->asAdministrator()->create();
        $region = Organization::factory()->asRegion()->create();

        $this->actingAs($admin)
            ->get(route('admin.organizations.update', ['organization' => $region]))
            ->assertOk()
            ->assertDontSee('Google Sync Sheet ID:');
    }

    public function test_seeder_stores_the_club_sheets(): void
    {
        $this->enablePlugin();
        $rebels = Organization::factory()->asOrganization()->create([
            Organization::NAME => 'Rebel Legion',
        ]);

        (new ClubSheetsSeeder)->run();

        $this->assertNotNull(OrganizationSheet::forOrganization($rebels->id));
    }

    public function test_index_names_fit_mysql_identifier_limit(): void
    {
        $this->enablePlugin();

        foreach (Schema::getIndexes('tt_google_sheets_organization_sheets') as $index)
        {
            $this->assertLessThanOrEqual(64, strlen($index['name']), $index['name']);
        }
    }

    private function organizationWithOldSheet(string $sheet_id): Organization
    {
        $organization = Organization::factory()->create();
        DB::table('tt_organizations')
            ->where('id', $organization->id)
            ->update(['sync_sheet_id' => $sheet_id]);

        return $organization;
    }

    private function enablePlugin(): void
    {
        $this->plugins->enable('google-sheets');
        $this->plugins->load($this->plugins->findOrFail('google-sheets'));
    }
}

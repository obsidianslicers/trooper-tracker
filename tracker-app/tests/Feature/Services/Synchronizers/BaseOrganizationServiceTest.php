<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Synchronizers;

use App\Contracts\MemberLookupInterface;
use App\Enums\MembershipStatus;
use App\Exceptions\GoogleSheetsUnavailableException;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use App\Services\GoogleService;
use App\Services\Synchronizers\BaseOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BaseOrganizationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_calls_synchronize_and_updates_organization_sync_timestamp(): void
    {
        $organization = Organization::factory()->create();
        $google = Mockery::mock(GoogleService::class);

        $subject = new BaseOrganizationServiceHarness($organization, $google);

        $subject->run();

        $organization->refresh();

        $this->assertSame(1, $subject->synchronize_call_count);
        $this->assertNotNull($organization->{Organization::SYNCHRONIZED_AT});
    }

    public function test_get_sheet_rows_returns_empty_when_sync_sheet_id_missing(): void
    {
        $organization = Organization::factory()->create([
            Organization::SYNC_SHEET_ID => null,
        ]);

        $google = Mockery::mock(GoogleService::class);
        $google->shouldNotReceive('getSheet');

        $subject = new BaseOrganizationServiceHarness($organization, $google);

        $result = $subject->publicGetSheetRows('Troopers');

        $this->assertSame([], $result);
    }

    public function test_get_sheet_rows_skips_header_by_default(): void
    {
        $organization = Organization::factory()->create([
            Organization::SYNC_SHEET_ID => 'sheet-123',
        ]);

        $google = Mockery::mock(GoogleService::class);
        $google->shouldReceive('getSheet')
            ->once()
            ->with('sheet-123', 'Troopers')
            ->andReturn([
                ['header_a', 'header_b'],
                ['row1_a', 'row1_b'],
            ]);

        $subject = new BaseOrganizationServiceHarness($organization, $google);

        $result = $subject->publicGetSheetRows('Troopers');

        $this->assertSame([['row1_a', 'row1_b']], $result);
    }

    public function test_get_sheet_rows_rethrows_and_leaves_sync_timestamp_stale_when_google_unavailable(): void
    {
        $organization = Organization::factory()->create([
            Organization::SYNC_SHEET_ID => 'sheet-123',
        ]);

        $google = Mockery::mock(GoogleService::class);
        $google->shouldReceive('getSheet')
            ->once()
            ->andThrow(new GoogleSheetsUnavailableException('Google Sheets is unavailable.'));

        $subject = new BaseOrganizationServiceHarness($organization, $google);

        $this->expectException(GoogleSheetsUnavailableException::class);

        try
        {
            $subject->publicGetSheetRows('Troopers');
        }
        finally
        {
            $organization->refresh();
            $this->assertNull($organization->{Organization::SYNCHRONIZED_AT});
        }
    }

    public function test_get_or_create_organization_costume_returns_cached_instance(): void
    {
        $organization = Organization::factory()->create();
        $google = Mockery::mock(GoogleService::class);
        $subject = new BaseOrganizationServiceHarness($organization, $google);

        $first = $subject->publicGetOrCreateOrganizationCostume('TK Armor', 'TK');
        $second = $subject->publicGetOrCreateOrganizationCostume('TK Armor', 'TK');

        $this->assertSame($first->id, $second->id);
        $fresh = OrganizationCostume::query()->find($first->id);

        $this->assertNotNull($fresh);
        $this->assertSame($organization->id, $fresh->{OrganizationCostume::ORGANIZATION_ID});
    }

    public function test_sync_trooper_status_updates_pivot_membership_status_and_sync_time(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->create();

        $pivot = TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-421',
        ]);

        $trooper_with_pivot = $organization->troopers()->where('tt_troopers.id', $trooper->id)->first();

        $google = Mockery::mock(GoogleService::class);
        $subject = new BaseOrganizationServiceHarness($organization, $google);

        $subject->publicSyncTrooperStatus($trooper_with_pivot, MembershipStatus::RESERVE);

        $pivot->refresh();

        $this->assertSame(MembershipStatus::RESERVE, $pivot->{TrooperOrganization::MEMBERSHIP_STATUS});
        $this->assertNotNull($pivot->{TrooperOrganization::SYNCHRONIZED_AT});
    }

    public function test_sync_trooper_costume_creates_or_updates_trooper_costume(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->create();
        $google = Mockery::mock(GoogleService::class);

        $subject = new BaseOrganizationServiceHarness($organization, $google);
        $org_costume = $subject->publicGetOrCreateOrganizationCostume('Scout Trooper');

        $subject->publicSyncTrooperCostume($trooper, $org_costume, [
            TrooperCostume::IMAGE_URL_LG => 'https://img/lg.jpg',
        ]);

        $saved = TrooperCostume::query()
            ->where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::ORGANIZATION_COSTUME_ID, $org_costume->id)
            ->first();

        $this->assertNotNull($saved);
        $this->assertSame('https://img/lg.jpg', $saved->{TrooperCostume::IMAGE_URL_LG});
    }

    public function test_clean_input_returns_null_for_null_and_sanitizes_strings(): void
    {
        $organization = Organization::factory()->create();
        $google = Mockery::mock(GoogleService::class);
        $subject = new BaseOrganizationServiceHarness($organization, $google);

        $this->assertNull($subject->publicCleanInput(null));
        $this->assertSame('&lt;b&gt;tag&lt;/b&gt;', $subject->publicCleanInput('<b>tag</b>'));
    }
}

class BaseOrganizationServiceHarness extends BaseOrganizationService
{
    public int $synchronize_call_count = 0;

    public function lookupMember(): ?MemberLookupInterface
    {
        return null;
    }

    protected function synchronize(): void
    {
        $this->synchronize_call_count++;
    }

    public function publicGetSheetRows(string $sheet_name, bool $skip_header = true): array
    {
        return $this->getSheetRows($sheet_name, $skip_header);
    }

    public function publicGetOrCreateOrganizationCostume(string $costume_name, ?string $prefix = null): OrganizationCostume
    {
        return $this->getOrCreateOrganizationCostume($costume_name, $prefix);
    }

    public function publicSyncTrooperStatus(Trooper $trooper, MembershipStatus $status): void
    {
        $this->syncTrooperStatus($trooper, $status);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function publicSyncTrooperCostume(Trooper $trooper, OrganizationCostume $org_costume, array $attributes): void
    {
        $this->syncTrooperCostume($trooper, $org_costume, $attributes);
    }

    public function publicCleanInput(mixed $value): mixed
    {
        return $this->cleanInput($value);
    }
}

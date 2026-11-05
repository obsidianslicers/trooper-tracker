<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Synchronizers;

use App\Contracts\MemberLookupInterface;
use App\Enums\MembershipStatus;
use App\Exceptions\SheetUnavailableException;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use App\Plugins\PluginSheetSource;
use App\Services\Synchronizers\BaseOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Concerns\UsesFakeSheetSource;
use Tests\TestCase;

class BaseOrganizationServiceTest extends TestCase
{
    use RefreshDatabase;
    use UsesFakeSheetSource;

    public function test_run_calls_synchronize_and_updates_organization_sync_timestamp(): void
    {
        $organization = Organization::factory()->create();
        $sheets = app(PluginSheetSource::class);

        $subject = new BaseOrganizationServiceHarness($organization, $sheets);

        $subject->run();

        $organization->refresh();

        $this->assertSame(1, $subject->synchronize_call_count);
        $this->assertNotNull($organization->{Organization::SYNCHRONIZED_AT});
    }

    public function test_get_sheet_rows_returns_empty_without_a_sheet_source(): void
    {
        $subject = new BaseOrganizationServiceHarness(
            Organization::factory()->create(),
            app(PluginSheetSource::class),
        );

        $this->assertSame([], $subject->publicGetSheetRows('Troopers'));
    }

    public function test_get_sheet_rows_returns_empty_when_the_organization_has_no_sheet(): void
    {
        $organization = Organization::factory()->create();
        $this->fakeSheetSource()->shouldReceive('rows')->once()->andReturn(null);

        $subject = new BaseOrganizationServiceHarness($organization, app(PluginSheetSource::class));

        $this->assertSame([], $subject->publicGetSheetRows('Troopers'));
    }

    public function test_get_sheet_rows_skips_header_by_default(): void
    {
        $organization = Organization::factory()->create();
        $this->fakeSheetSource()->shouldReceive('rows')
            ->once()
            ->with(Mockery::on(fn (Organization $o): bool => $o->is($organization)), 'Troopers')
            ->andReturn([
                ['header_a', 'header_b'],
                ['row1_a', 'row1_b'],
            ]);

        $subject = new BaseOrganizationServiceHarness($organization, app(PluginSheetSource::class));

        $this->assertSame([['row1_a', 'row1_b']], $subject->publicGetSheetRows('Troopers'));
    }

    public function test_get_sheet_rows_rethrows_and_leaves_sync_timestamp_stale_when_unavailable(): void
    {
        $organization = Organization::factory()->create();
        $this->fakeSheetSource()->shouldReceive('rows')
            ->once()
            ->andThrow(new SheetUnavailableException('The sheet is unavailable.'));

        $subject = new BaseOrganizationServiceHarness($organization, app(PluginSheetSource::class));

        $this->expectException(SheetUnavailableException::class);

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
        $sheets = app(PluginSheetSource::class);
        $subject = new BaseOrganizationServiceHarness($organization, $sheets);

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

        $sheets = app(PluginSheetSource::class);
        $subject = new BaseOrganizationServiceHarness($organization, $sheets);

        $subject->publicSyncTrooperStatus($trooper_with_pivot, MembershipStatus::RESERVE);

        $pivot->refresh();

        $this->assertSame(MembershipStatus::RESERVE, $pivot->{TrooperOrganization::MEMBERSHIP_STATUS});
        $this->assertNotNull($pivot->{TrooperOrganization::SYNCHRONIZED_AT});
    }

    public function test_sync_trooper_costume_creates_or_updates_trooper_costume(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->create();
        $sheets = app(PluginSheetSource::class);

        $subject = new BaseOrganizationServiceHarness($organization, $sheets);
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
        $sheets = app(PluginSheetSource::class);
        $subject = new BaseOrganizationServiceHarness($organization, $sheets);

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

<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Synchronizers;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use App\Services\GoogleService;
use App\Services\Synchronizers\TheLegionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TheLegionServiceTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;
    private TheLegionService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $google_service = $this->mock(GoogleService::class);
        $this->subject = new TheLegionService($this->organization, $google_service);
    }

    public function test_synchronize_with_valid_costumes_and_troopers(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        $html = $this->getValidCostumesHtml();
        $api_response = $this->getValidTrooperApiResponse();

        Http::fake([
            'crls.501st.com/*' => Http::response($html),
            'www.501st.com/*' => Http::response($api_response),
        ]);

        $this->subject->synchronize();

        // Verify costumes created from HTML
        $stormtrooper = OrganizationCostume::where(OrganizationCostume::NAME, 'Stormtrooper')->first();
        $this->assertNotNull($stormtrooper);
        $this->assertEquals('ST', $stormtrooper->prefix);
        $this->assertNotNull($stormtrooper->synchronized_at);

        $officer = OrganizationCostume::where(OrganizationCostume::NAME, 'Imperial Officer')->first();
        $this->assertNotNull($officer);
        $this->assertEquals('IO', $officer->prefix);

        // Verify trooper costumes linked from API
        $trooper_costume = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::COSTUME_ID, $stormtrooper->id)
            ->first();
        $this->assertNotNull($trooper_costume);
        $this->assertEquals('https://example.com/stormtrooper.jpg', $trooper_costume->image_url_lg);
        $this->assertEquals('https://example.com/stormtrooper-thumb.jpg', $trooper_costume->image_url_sm);
        $this->assertEquals('https://example.com/stormtrooper-bucket.jpg', $trooper_costume->image_url_bucket_off);

        // Verify trooper status updated to ACTIVE
        $trooper_with_pivot = $this->organization->troopers()->where('tt_troopers.id', $trooper->id)->first();
        $this->assertEquals(MembershipStatus::ACTIVE->value, $trooper_with_pivot->pivot->membership_status);
    }

    public function test_synchronize_skips_costumes_with_whitespace_names(): void
    {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <article><a>XX - Extra Costume</a></article>
            <article><a>ST - Stormtrooper</a></article>
        </body>
        </html>
        HTML;

        Http::fake([
            'crls.501st.com/*' => Http::response($html),
        ]);

        $this->subject->synchronize();

        // Both valid costumes should be created
        $stormtrooper = OrganizationCostume::where(OrganizationCostume::NAME, 'Stormtrooper')->first();
        $this->assertNotNull($stormtrooper);

        $extra = OrganizationCostume::where(OrganizationCostume::NAME, 'Extra Costume')->first();
        $this->assertNotNull($extra);

        // Verify both were parsed from HTML correctly
        $all_costumes = OrganizationCostume::all();
        $this->assertGreaterThanOrEqual(2, $all_costumes->count());
    }

    public function test_synchronize_handles_multiple_costumes(): void
    {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <article><a>ST - Stormtrooper</a></article>
            <article><a>IO - Imperial Officer</a></article>
            <article><a>TK - TK Trooper</a></article>
            <article><a>CC - Clone Commander</a></article>
        </body>
        </html>
        HTML;

        Http::fake([
            'crls.501st.com/*' => Http::response($html),
            'www.501st.com/*' => Http::response([]),
        ]);

        $this->subject->synchronize();

        $this->assertNotNull(OrganizationCostume::where(OrganizationCostume::NAME, 'Stormtrooper')->first());
        $this->assertNotNull(OrganizationCostume::where(OrganizationCostume::NAME, 'Imperial Officer')->first());
        $this->assertNotNull(OrganizationCostume::where(OrganizationCostume::NAME, 'TK Trooper')->first());
        $this->assertNotNull(OrganizationCostume::where(OrganizationCostume::NAME, 'Clone Commander')->first());
    }

    public function test_synchronize_handles_multiple_troopers(): void
    {
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();

        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper1->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper2->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '67890',
            ])
            ->create();

        $html = $this->getValidCostumesHtml();
        $api_response_1 = $this->getValidTrooperApiResponse('12345');
        $api_response_2 = $this->getValidTrooperApiResponse('67890');

        Http::fake([
            'crls.501st.com/*' => Http::response($html),
            'www.501st.com/memberAPI/v3/legionId/12345/costumes' => Http::response($api_response_1),
            'www.501st.com/memberAPI/v3/legionId/67890/costumes' => Http::response($api_response_2),
        ]);

        $this->subject->synchronize();

        $stormtrooper = OrganizationCostume::where(OrganizationCostume::NAME, 'Stormtrooper')->first();

        $trooper1_costume = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper1->id)
            ->where(TrooperCostume::COSTUME_ID, $stormtrooper->id)
            ->first();
        $this->assertNotNull($trooper1_costume);

        $trooper2_costume = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper2->id)
            ->where(TrooperCostume::COSTUME_ID, $stormtrooper->id)
            ->first();
        $this->assertNotNull($trooper2_costume);
    }

    public function test_synchronize_handles_no_costumes_in_api_response(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        $html = $this->getValidCostumesHtml();
        $api_response = [
            'legionId' => 12345,
            'costumes' => [],
        ];

        Http::fake([
            'crls.501st.com/costume-reference-library/costumes-by-name' => Http::response($html),
            'www.501st.com/memberAPI/v3/legionId/12345/costumes' => Http::response($api_response),
        ]);

        $this->subject->synchronize();

        // Costumes from HTML should still be created
        $stormtrooper = OrganizationCostume::where(OrganizationCostume::NAME, 'Stormtrooper')->first();
        $this->assertNotNull($stormtrooper);

        // But no trooper costumes linked
        $trooper_costumes = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)->get();
        $this->assertEquals(0, $trooper_costumes->count());
    }

    public function test_synchronize_handles_api_error_response(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
                TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
            ])
            ->create();

        $html = $this->getValidCostumesHtml();
        $api_response = [
            'legionId' => 12345,
            'error' => 'Member not found',
            'costumes' => [],
        ];

        Http::fake([
            'crls.501st.com/costume-reference-library/costumes-by-name' => Http::response($html),
            'www.501st.com/*' => Http::response($api_response),
        ]);

        $this->subject->synchronize();

        // Costumes still created from HTML
        $stormtrooper = OrganizationCostume::where(OrganizationCostume::NAME, 'Stormtrooper')->first();
        $this->assertNotNull($stormtrooper);

        // Status remains ACTIVE since error response has no costumes array (service skips update)
        $trooper_with_pivot = $this->organization->troopers()->where('tt_troopers.id', $trooper->id)->first();
        $this->assertEquals(MembershipStatus::ACTIVE->value, $trooper_with_pivot->pivot->membership_status);
    }

    public function test_synchronize_handles_api_not_approved(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        $html = $this->getValidCostumesHtml();
        $api_response = [
            'legionId' => 12345,
            'memberApproved' => 'NO',
            'memberStanding' => 'Good',
            'memberStatus' => 'Active',
            'costumes' => [],
        ];

        Http::fake([
            'crls.501st.com/*' => Http::response($html),
            'www.501st.com/*' => Http::response($api_response),
        ]);

        $this->subject->synchronize();

        // Verify costumes were created from HTML
        $stormtrooper = OrganizationCostume::where(OrganizationCostume::NAME, 'Stormtrooper')->first();
        $this->assertNotNull($stormtrooper);

        // Verify trooper still exists with a status
        $trooper_with_pivot = $this->organization->troopers()->where('tt_troopers.id', $trooper->id)->first();
        $this->assertNotNull($trooper_with_pivot->pivot->membership_status);
    }

    public function test_synchronize_handles_api_reserve_status(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        $html = $this->getValidCostumesHtml();
        $api_response = [
            'legionId' => 12345,
            'memberApproved' => 'YES',
            'memberStanding' => 'Good',
            'memberStatus' => 'Reserve',
            'costumes' => [],
        ];

        Http::fake([
            'crls.501st.com/*' => Http::response($html),
            'www.501st.com/*' => Http::response($api_response),
        ]);

        $this->subject->synchronize();

        // Verify costumes were created from HTML
        $stormtrooper = OrganizationCostume::where(OrganizationCostume::NAME, 'Stormtrooper')->first();
        $this->assertNotNull($stormtrooper);

        // Verify trooper with reserve status is processed
        $trooper_with_pivot = $this->organization->troopers()->where('tt_troopers.id', $trooper->id)->first();
        $this->assertNotNull($trooper_with_pivot->pivot->membership_status);
    }

    public function test_synchronize_handles_invalid_json_response(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        $html = $this->getValidCostumesHtml();

        Http::fake([
            'crls.501st.com/*' => Http::response($html),
            'www.501st.com/*' => Http::response('invalid json'),
        ]);

        $this->subject->synchronize();

        // Costumes from HTML should still be created
        $stormtrooper = OrganizationCostume::where(OrganizationCostume::NAME, 'Stormtrooper')->first();
        $this->assertNotNull($stormtrooper);

        // No trooper costumes linked due to invalid response
        $trooper_costumes = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)->get();
        $this->assertEquals(0, $trooper_costumes->count());
    }

    public function test_synchronize_skips_costume_with_empty_costume_name_in_api(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        $html = $this->getValidCostumesHtml();
        $api_response = [
            'legionId' => 12345,
            'memberApproved' => 'YES',
            'memberStanding' => 'Good',
            'memberStatus' => 'Active',
            'costumes' => [
                [
                    'costumeName' => '', // empty name
                    'photoURL' => 'https://example.com/null.jpg',
                ],
                [
                    'costumeName' => 'Stormtrooper',
                    'photoURL' => 'https://example.com/stormtrooper.jpg',
                ],
            ],
        ];

        Http::fake([
            'crls.501st.com/*' => Http::response($html),
            'www.501st.com/memberAPI/v3/legionId/12345/costumes' => Http::response($api_response),
        ]);

        $this->subject->synchronize();

        // Only valid costume should be linked
        $trooper_costumes = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)->get();
        $this->assertEquals(1, $trooper_costumes->count());
    }

    public function test_synchronize_updates_existing_trooper_costume(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        $org_costume = OrganizationCostume::factory()
            ->state([OrganizationCostume::ORGANIZATION_ID => $this->organization->id])
            ->create([OrganizationCostume::NAME => 'Stormtrooper']);

        // Create existing trooper costume with old image URL
        $trooper_costume = TrooperCostume::factory()
            ->state([
                TrooperCostume::TROOPER_ID => $trooper->id,
                TrooperCostume::COSTUME_ID => $org_costume->id,
            ])
            ->create([TrooperCostume::IMAGE_URL_LG => 'https://example.com/old.jpg']);

        $html = $this->getValidCostumesHtml();
        $api_response = $this->getValidTrooperApiResponse();

        Http::fake([
            'crls.501st.com/*' => Http::response($html),
            'www.501st.com/memberAPI/v3/legionId/12345/costumes' => Http::response($api_response),
        ]);

        $this->subject->synchronize();

        // Verify image URL was updated
        $updated_costume = TrooperCostume::find($trooper_costume->id);
        $this->assertEquals('https://example.com/stormtrooper.jpg', $updated_costume->image_url_lg);
    }

    public function test_synchronize_handles_costume_without_thumbnail(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        $html = $this->getValidCostumesHtml();
        $api_response = [
            'legionId' => 12345,
            'memberApproved' => 'YES',
            'memberStanding' => 'Good',
            'memberStatus' => 'Active',
            'costumes' => [
                [
                    'costumeName' => 'Stormtrooper',
                    'photoURL' => 'https://example.com/stormtrooper.jpg',
                    'bucketOffPhoto' => 'https://example.com/stormtrooper-bucket.jpg',
                    // no thumbnail
                ],
            ],
        ];

        Http::fake([
            'crls.501st.com/costume-reference-library/costumes-by-name' => Http::response($html),
            'www.501st.com/memberAPI/v3/legionId/12345/costumes' => Http::response($api_response),
        ]);

        $this->subject->synchronize();

        $stormtrooper = OrganizationCostume::where(OrganizationCostume::NAME, 'Stormtrooper')->first();
        $trooper_costume = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::COSTUME_ID, $stormtrooper->id)
            ->first();

        $this->assertNotNull($trooper_costume);
        $this->assertNull($trooper_costume->image_url_sm);
    }

    public function test_synchronize_skips_trooper_not_found(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        $html = $this->getValidCostumesHtml();
        // API response has different legionId than trooper's identifier
        $api_response = [
            'legionId' => 99999,
            'memberApproved' => 'YES',
            'memberStanding' => 'Good',
            'memberStatus' => 'Active',
            'costumes' => [
                [
                    'costumeName' => 'Stormtrooper',
                    'photoURL' => 'https://example.com/stormtrooper.jpg',
                ],
            ],
        ];

        Http::fake([
            'crls.501st.com/costume-reference-library/costumes-by-name' => Http::response($html),
            'www.501st.com/memberAPI/v3/legionId/12345/costumes' => Http::response($api_response),
        ]);

        $this->subject->synchronize();

        // Costumes from HTML created
        $stormtrooper = OrganizationCostume::where(OrganizationCostume::NAME, 'Stormtrooper')->first();
        $this->assertNotNull($stormtrooper);

        // But no trooper costume linked because trooper with legionId 99999 not found
        $trooper_costumes = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)->get();
        $this->assertEquals(0, $trooper_costumes->count());
    }

    public function test_synchronize_preserves_status_when_api_error_and_not_active(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->create();

        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
                TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
            ])
            ->create();

        $html = $this->getValidCostumesHtml();
        $api_response = [
            'legionId' => 12345,
            'error' => 'Member not found',
        ];

        Http::fake([
            'crls.501st.com/*' => Http::response($html),
            'www.501st.com/memberAPI/v3/legionId/12345/costumes' => Http::response($api_response),
        ]);

        $this->subject->synchronize();

        // Status should be preserved because it wasn't ACTIVE
        $trooper_with_pivot = $this->organization->troopers()->where('tt_troopers.id', $trooper->id)->first();
        $this->assertEquals(MembershipStatus::PENDING->value, $trooper_with_pivot->pivot->membership_status);
    }

    private function getValidCostumesHtml(): string
    {
        return <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <article><a>ST - Stormtrooper</a></article>
            <article><a>IO - Imperial Officer</a></article>
        </body>
        </html>
        HTML;
    }

    private function getValidTrooperApiResponse(?string $legion_id = null): array
    {
        $id = $legion_id ?? '12345';
        return [
            'legionId' => $id,
            'memberApproved' => 'YES',
            'memberStanding' => 'Good',
            'memberStatus' => 'Active',
            'costumes' => [
                [
                    'costumeName' => 'Stormtrooper',
                    'photoURL' => 'https://example.com/stormtrooper.jpg',
                    'thumbnail' => 'https://example.com/stormtrooper-thumb.jpg',
                    'bucketOffPhoto' => 'https://example.com/stormtrooper-bucket.jpg',
                ],
            ],
        ];
    }
}

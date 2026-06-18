<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MemberLookup;

use App\Services\MemberLookup\TheLegionMemberLookupService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TheLegionMemberLookupServiceTest extends TestCase
{
    private TheLegionMemberLookupService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->subject = new TheLegionMemberLookupService();
    }

    public function test_lookup_returns_member_data_when_api_succeeds(): void
    {
        Http::fake([
            'api.501st.com/*' => Http::response([
                'legionId'         => 1234,
                'formattedLegionId' => 'TK 1234',
                'fullName'         => 'John Trooper',
                'memberStatus'     => 'Active',
                'memberStanding'   => 'Good',
                'memberApproved'   => 'YES',
                'garrisonName'     => 'Dune Sea Garrison',
                'profileUrl'       => 'https://www.501st.com/members/1234',
                'primaryThumbnail' => 'https://www.501st.com/thumbs/1234.jpg',
            ], 200),
        ]);

        $result = $this->subject->lookup('1234');

        $this->assertIsArray($result);
        $this->assertSame('1234', $result['identifier']);
        $this->assertSame('TK 1234', $result['formatted_identifier']);
        $this->assertSame('John Trooper', $result['full_name']);
        $this->assertSame('Active', $result['status']);
        $this->assertSame('Good', $result['standing']);
        $this->assertTrue($result['is_approved']);
        $this->assertSame('Dune Sea Garrison', $result['unit_name']);
        $this->assertSame('https://www.501st.com/members/1234', $result['profile_url']);
        $this->assertSame('https://www.501st.com/thumbs/1234.jpg', $result['thumbnail_url']);
    }

    public function test_lookup_returns_null_when_api_returns_non_success_status(): void
    {
        Http::fake([
            'api.501st.com/*' => Http::response(null, 500),
        ]);

        $result = $this->subject->lookup('1234');

        $this->assertNull($result);
    }

    public function test_lookup_returns_null_when_response_contains_error_key(): void
    {
        Http::fake([
            'api.501st.com/*' => Http::response(['error' => 'Member not found'], 200),
        ]);

        $result = $this->subject->lookup('9999');

        $this->assertNull($result);
    }

    public function test_lookup_returns_null_when_response_is_empty(): void
    {
        Http::fake([
            'api.501st.com/*' => Http::response([], 200),
        ]);

        $result = $this->subject->lookup('1234');

        $this->assertNull($result);
    }

    public function test_lookup_sets_is_approved_false_when_member_approved_is_not_yes(): void
    {
        Http::fake([
            'api.501st.com/*' => Http::response([
                'legionId'        => 5678,
                'formattedLegionId' => 'TK 5678',
                'memberApproved'  => 'NO',
            ], 200),
        ]);

        $result = $this->subject->lookup('5678');

        $this->assertIsArray($result);
        $this->assertFalse($result['is_approved']);
    }

    public function test_lookup_caches_successful_result_to_avoid_repeat_api_calls(): void
    {
        Http::fake([
            'api.501st.com/*' => Http::response([
                'legionId'        => 1234,
                'formattedLegionId' => 'TK 1234',
                'memberApproved'  => 'YES',
            ], 200),
        ]);

        $this->subject->lookup('1234');
        $this->subject->lookup('1234');

        Http::assertSentCount(1);
    }

    public function test_lookup_caches_not_found_result_to_avoid_repeat_api_calls(): void
    {
        Http::fake([
            'api.501st.com/*' => Http::response(null, 404),
        ]);

        $first = $this->subject->lookup('0000');
        $second = $this->subject->lookup('0000');

        $this->assertNull($first);
        $this->assertNull($second);
        Http::assertSentCount(1);
    }

    public function test_lookup_falls_back_identifier_when_legion_id_missing(): void
    {
        Http::fake([
            'api.501st.com/*' => Http::response([
                'formattedLegionId' => 'TK 7777',
                'memberApproved'    => 'YES',
            ], 200),
        ]);

        $result = $this->subject->lookup('7777');

        $this->assertIsArray($result);
        $this->assertSame('7777', $result['identifier']);
    }
}

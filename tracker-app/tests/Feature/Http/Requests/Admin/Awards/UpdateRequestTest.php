<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Awards;

use App\Http\Requests\Admin\Awards\UpdateRequest;
use App\Models\Award;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $moderator;

    private Organization $organization;

    private Award $award;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->moderator = Trooper::factory()->asModerator()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $this->moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $this->organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $this->award = Award::factory()->create([
            Award::ORGANIZATION_ID => $this->organization->id,
        ]);

        $this->actingAs($this->moderator);
    }

    /**
     * Helper method to set up a mocked route parameter
     */
    private function setupMockedRoute(UpdateRequest $request, ?Award $award): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('award')
            ->andReturn($award);
        $mock_route->shouldReceive('parameter')
            ->with('award', \Mockery::any())
            ->andReturn($award);
        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_returns_true_for_moderator(): void
    {
        $subject = new UpdateRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $this->setupMockedRoute($subject, $this->award);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_throws_exception_when_award_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Award not found or unauthorized.');

        $subject = new UpdateRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_requires_name(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->award);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Award::NAME, $rules);
        $this->assertContains('required', $rules[Award::NAME]);
    }

    public function test_rules_validates_name_is_string(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->award);
        $rules = $subject->rules();

        $this->assertContains('string', $rules[Award::NAME]);
    }

    public function test_rules_validates_name_max_length(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->award);

        $validator = Validator::make(
            [
                Award::NAME => str_repeat('a', 129), // max is 128
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Award::NAME, $validator->errors()->toArray());
    }

    public function test_rules_accepts_valid_name(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->award);

        $validator = Validator::make(
            [
                Award::NAME => 'Updated Award Name',
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_does_not_include_frequency(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->award);
        $rules = $subject->rules();

        // Frequency is immutable after creation
        $this->assertArrayNotHasKey(Award::FREQUENCY, $rules);
    }

    public function test_rules_does_not_include_organization_id(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->award);
        $rules = $subject->rules();

        // Organization is immutable after creation
        $this->assertArrayNotHasKey(Award::ORGANIZATION_ID, $rules);
    }

    public function test_rules_validates_name_within_128_characters(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->award);

        $validator = Validator::make(
            [
                Award::NAME => str_repeat('a', 128), // exactly 128 chars
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }
}

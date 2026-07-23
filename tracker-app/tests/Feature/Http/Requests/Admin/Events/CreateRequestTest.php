<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Events;

use App\Http\Requests\Admin\Events\CreateRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CreateRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $moderator;

    private Organization $organization;

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

        $this->actingAs($this->moderator);
    }

    public function test_authorize_returns_true_for_moderator(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_regular_member(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $member);

        $this->assertFalse($subject->authorize());
    }

    public function test_rules_requires_organization_id(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Event::ORGANIZATION_ID, $rules);
        $this->assertContains('required', $rules[Event::ORGANIZATION_ID]);
    }

    public function test_rules_validates_organization_exists(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $validator = Validator::make(
            [
                Event::ORGANIZATION_ID => 999999, // Non-existent
                Event::NAME => 'Test Event',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Event::ORGANIZATION_ID, $validator->errors()->toArray());
    }

    public function test_rules_validates_organization_is_moderated_by_user(): void
    {
        $other_organization = Organization::factory()->create();
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $validator = Validator::make(
            [
                Event::ORGANIZATION_ID => $other_organization->id,
                Event::NAME => 'Test Event',
            ],
            $subject->rules()
        );

        // Should fail because moderator doesn't moderate this organization
        $this->assertTrue($validator->fails());
    }

    public function test_rules_accepts_valid_organization_for_moderator(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $validator = Validator::make(
            [
                Event::ORGANIZATION_ID => $this->organization->id,
                Event::NAME => 'Test Event',
            ],
            $subject->rules()
        );

        // May fail on other common rules, but organization validation should pass
        $this->assertFalse($validator->errors()->has(Event::ORGANIZATION_ID));
    }

    public function test_rules_includes_source_field(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $rules = $subject->rules();

        $this->assertArrayHasKey('source', $rules);
        $this->assertContains('nullable', $rules['source']);
    }

    public function test_rules_validates_source_is_string(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $rules = $subject->rules();

        $this->assertContains('string', $rules['source']);
    }

    public function test_rules_includes_common_event_fields(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $rules = $subject->rules();

        // Verify common rules are included (from CommonRules trait)
        $this->assertIsArray($rules);
        $this->assertNotEmpty($rules);
    }

    public function test_rules_rejects_event_end_before_event_start(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $validator = Validator::make(
            [
                Event::ORGANIZATION_ID => $this->organization->id,
                Event::NAME => 'Test Event',
                Event::TYPE => 'other',
                Event::STATUS => 'open',
                Event::EVENT_START => '2024-01-15 14:00:00',
                Event::EVENT_END => '2024-01-15 13:00:00',
                Event::TENTATIVE_SIGNUPS_ALLOWED => false,
                Event::SECURE_STAGING_AREA => false,
                Event::ALLOW_BLASTERS => false,
                Event::ALLOW_PROPS => false,
                Event::PARKING_AVAILABLE => false,
                Event::ACCESSIBLE => false,
                Event::REQUIRE_MISSION_BRIEF_ACK => false,
                Event::CREATE_FORUM_THREAD => false,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Event::EVENT_END, $validator->errors()->toArray());
    }

    public function test_rules_accepts_event_end_after_event_start(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $validator = Validator::make(
            [
                Event::ORGANIZATION_ID => $this->organization->id,
                Event::NAME => 'Test Event',
                Event::TYPE => 'other',
                Event::STATUS => 'open',
                Event::EVENT_START => '2024-01-15 13:00:00',
                Event::EVENT_END => '2024-01-15 14:00:00',
                Event::TENTATIVE_SIGNUPS_ALLOWED => false,
                Event::SECURE_STAGING_AREA => false,
                Event::ALLOW_BLASTERS => false,
                Event::ALLOW_PROPS => false,
                Event::PARKING_AVAILABLE => false,
                Event::ACCESSIBLE => false,
                Event::REQUIRE_MISSION_BRIEF_ACK => false,
                Event::CREATE_FORUM_THREAD => false,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->errors()->has(Event::EVENT_END));
    }
}

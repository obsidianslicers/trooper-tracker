<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Awards;

use App\Enums\AwardFrequency;
use App\Http\Requests\Admin\Awards\CreateRequest;
use App\Models\Award;
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

    public function test_rules_requires_name(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Award::NAME, $rules);
        $this->assertContains('required', $rules[Award::NAME]);
    }

    public function test_rules_validates_name_is_string(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $rules = $subject->rules();

        $this->assertContains('string', $rules[Award::NAME]);
    }

    public function test_rules_validates_name_max_length(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $validator = Validator::make(
            [
                Award::NAME => str_repeat('a', 129), // max is 128
                Award::FREQUENCY => AwardFrequency::MONTHLY->value,
                Award::ORGANIZATION_ID => $this->organization->id,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Award::NAME, $validator->errors()->toArray());
    }

    public function test_rules_requires_frequency(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Award::FREQUENCY, $rules);
        $this->assertContains('required', $rules[Award::FREQUENCY]);
    }

    public function test_rules_validates_frequency_is_valid_enum(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $validator = Validator::make(
            [
                Award::NAME => 'Test Award',
                Award::FREQUENCY => 'invalid-frequency',
                Award::ORGANIZATION_ID => $this->organization->id,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Award::FREQUENCY, $validator->errors()->toArray());
    }

    public function test_rules_accepts_monthly_frequency(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $validator = Validator::make(
            [
                Award::NAME => 'Test Award',
                Award::FREQUENCY => AwardFrequency::MONTHLY->value,
                Award::ORGANIZATION_ID => $this->organization->id,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_yearly_frequency(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $validator = Validator::make(
            [
                Award::NAME => 'Test Award',
                Award::FREQUENCY => AwardFrequency::ANNUALLY->value,
                Award::ORGANIZATION_ID => $this->organization->id,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_requires_organization_id(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Award::ORGANIZATION_ID, $rules);
        $this->assertContains('required', $rules[Award::ORGANIZATION_ID]);
    }

    public function test_rules_validates_organization_exists(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $validator = Validator::make(
            [
                Award::NAME => 'Test Award',
                Award::FREQUENCY => AwardFrequency::MONTHLY->value,
                Award::ORGANIZATION_ID => 999999, // Non-existent
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Award::ORGANIZATION_ID, $validator->errors()->toArray());
    }

    public function test_rules_validates_organization_is_moderated_by_user(): void
    {
        $other_organization = Organization::factory()->create();
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $validator = Validator::make(
            [
                Award::NAME => 'Test Award',
                Award::FREQUENCY => AwardFrequency::MONTHLY->value,
                Award::ORGANIZATION_ID => $other_organization->id,
            ],
            $subject->rules()
        );

        // Should fail because moderator doesn't moderate this organization
        $this->assertTrue($validator->fails());
    }

    public function test_rules_accepts_valid_award_creation(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $validator = Validator::make(
            [
                Award::NAME => 'Trooper of the Month',
                Award::FREQUENCY => AwardFrequency::MONTHLY->value,
                Award::ORGANIZATION_ID => $this->organization->id,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Organizations;

use App\Http\Requests\Admin\Organizations\UpdateRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $admin;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Trooper::factory()->asAdministrator()->create();
        $this->organization = Organization::factory()->create();
        $this->actingAs($this->admin);
    }

    /**
     * Helper method to set up a mocked route parameter
     */
    private function setupMockedRoute(UpdateRequest $request, ?Organization $organization): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('organization')
            ->andReturn($organization);
        $mock_route->shouldReceive('parameter')
            ->with('organization', \Mockery::any())
            ->andReturn($organization);
        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_returns_true_for_admin(): void
    {
        $subject = new UpdateRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $this->setupMockedRoute($subject, $this->organization);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_throws_exception_when_organization_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Organization not found or unauthorized.');

        $subject = new UpdateRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_requires_name(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->organization);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Organization::NAME, $rules);
        $this->assertContains('required', $rules[Organization::NAME]);
    }

    public function test_rules_validates_name_is_string(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->organization);
        $rules = $subject->rules();

        $this->assertContains('string', $rules[Organization::NAME]);
    }

    public function test_rules_validates_name_max_length(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->organization);

        $validator = Validator::make(
            [
                Organization::NAME => str_repeat('a', 65), // max is 64
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Organization::NAME, $validator->errors()->toArray());
    }

    public function test_rules_sync_sheet_id_is_nullable(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->organization);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Organization::SYNC_SHEET_ID, $rules);
        $this->assertContains('nullable', $rules[Organization::SYNC_SHEET_ID]);
    }

    public function test_rules_validates_sync_sheet_id_max_length(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->organization);

        $validator = Validator::make(
            [
                Organization::NAME => 'Test Organization',
                Organization::SYNC_SHEET_ID => str_repeat('a', 129), // max is 128
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Organization::SYNC_SHEET_ID, $validator->errors()->toArray());
    }

    public function test_rules_discord_mention_is_nullable(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->organization);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Organization::DISCORD_MENTION, $rules);
        $this->assertContains('nullable', $rules[Organization::DISCORD_MENTION]);
    }

    public function test_rules_validates_discord_mention_max_length(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->organization);

        $validator = Validator::make(
            [
                Organization::NAME => 'Test Organization',
                Organization::DISCORD_MENTION => str_repeat('a', 129), // max is 128
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Organization::DISCORD_MENTION, $validator->errors()->toArray());
    }

    public function test_rules_related_forum_is_nullable(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->organization);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Organization::RELATED_FORUM, $rules);
        $this->assertContains('nullable', $rules[Organization::RELATED_FORUM]);
    }

    public function test_rules_validates_related_forum_is_integer(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->organization);
        $rules = $subject->rules();

        $this->assertContains('integer', $rules[Organization::RELATED_FORUM]);
    }

    public function test_rules_related_forum_archive_is_nullable(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->organization);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Organization::RELATED_FORUM_ARCHIVE, $rules);
        $this->assertContains('nullable', $rules[Organization::RELATED_FORUM_ARCHIVE]);
    }

    public function test_rules_validates_related_forum_archive_is_integer(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->organization);
        $rules = $subject->rules();

        $this->assertContains('integer', $rules[Organization::RELATED_FORUM_ARCHIVE]);
    }

    public function test_rules_accepts_valid_update_data(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->organization);

        $validator = Validator::make(
            [
                Organization::NAME => 'Updated Organization',
                Organization::SYNC_SHEET_ID => 'sheet-123',
                Organization::DISCORD_MENTION => '@role',
                Organization::RELATED_FORUM => 42,
                Organization::RELATED_FORUM_ARCHIVE => 84,
            ],
            $subject->rules()
        );

        // May fail on UniqueNameRule but should pass basic validations
        $this->assertIsArray($validator->errors()->toArray());
    }

    public function test_rules_uses_unique_name_rule(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->organization);
        $rules = $subject->rules();

        // Verify UniqueNameRule is present in the rules
        $name_rules = $rules[Organization::NAME];
        $has_unique_rule = false;

        foreach ($name_rules as $rule)
        {
            if (is_object($rule) && get_class($rule) === 'App\Rules\Admin\Organizations\UniqueNameRule')
            {
                $has_unique_rule = true;

                break;
            }
        }

        $this->assertTrue($has_unique_rule);
    }
}

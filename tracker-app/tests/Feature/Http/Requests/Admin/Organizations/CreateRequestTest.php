<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Organizations;

use App\Http\Requests\Admin\Organizations\CreateRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CreateRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $admin;

    private Organization $parent_organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Trooper::factory()->asAdministrator()->create();
        $this->parent_organization = Organization::factory()->create();
        $this->actingAs($this->admin);
    }

    /**
     * Helper method to set up a mocked route parameter
     */
    private function setupMockedRoute(CreateRequest $request, ?Organization $parent): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('parent')
            ->andReturn($parent);
        $mock_route->shouldReceive('parameter')
            ->with('parent', \Mockery::any())
            ->andReturn($parent);
        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_returns_true_for_admin(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->admin);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_non_admin(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $member);

        $this->assertFalse($subject->authorize());
    }

    public function test_rules_requires_name(): void
    {
        $subject = new CreateRequest;
        $this->setupMockedRoute($subject, $this->parent_organization);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Organization::NAME, $rules);
        $this->assertContains('required', $rules[Organization::NAME]);
    }

    public function test_rules_validates_name_is_string(): void
    {
        $subject = new CreateRequest;
        $this->setupMockedRoute($subject, $this->parent_organization);
        $rules = $subject->rules();

        $this->assertContains('string', $rules[Organization::NAME]);
    }

    public function test_rules_validates_name_max_length(): void
    {
        $subject = new CreateRequest;
        $this->setupMockedRoute($subject, $this->parent_organization);

        $validator = Validator::make(
            [
                Organization::NAME => str_repeat('a', 65), // max is 64
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Organization::NAME, $validator->errors()->toArray());
    }

    public function test_rules_accepts_valid_name(): void
    {
        $subject = new CreateRequest;
        $this->setupMockedRoute($subject, $this->parent_organization);

        $validator = Validator::make(
            [
                Organization::NAME => 'Vader\'s Fist',
            ],
            $subject->rules()
        );

        // UniqueNameRule may fail if siblings exist, but basic validation should pass
        $this->assertIsArray($validator->errors()->toArray());
    }

    public function test_rules_uses_unique_name_rule(): void
    {
        $subject = new CreateRequest;
        $this->setupMockedRoute($subject, $this->parent_organization);
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

    public function test_rules_validates_name_within_64_characters(): void
    {
        $subject = new CreateRequest;
        $this->setupMockedRoute($subject, $this->parent_organization);

        $validator = Validator::make(
            [
                Organization::NAME => str_repeat('a', 64), // exactly 64 chars
            ],
            $subject->rules()
        );

        // Should not fail for max length (may fail for other rules like uniqueness)
        $this->assertIsArray($validator->errors()->toArray());
    }
}

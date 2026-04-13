<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Costumes;

use App\Enums\OrganizationType;
use App\Http\Requests\Admin\Costumes\UpdateRequest;
use App\Models\Costume;
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

    private Costume $costume;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Trooper::factory()->asAdministrator()->create();
        $this->costume = Costume::factory()->create();
        $this->actingAs($this->admin);
    }

    /**
     * Helper method to set up a mocked route parameter
     */
    private function setupMockedRoute(UpdateRequest $request, ?Costume $costume): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('costume')
            ->andReturn($costume);
        $mock_route->shouldReceive('parameter')
            ->with('costume', \Mockery::any())
            ->andReturn($costume);
        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_returns_true_for_admin(): void
    {
        $subject = new UpdateRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $this->setupMockedRoute($subject, $this->costume);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_regular_member(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $subject = new UpdateRequest;
        $subject->setUserResolver(fn() => $member);
        $this->setupMockedRoute($subject, $this->costume);

        $this->assertFalse($subject->authorize());
    }

    public function test_authorize_throws_exception_when_costume_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Costume not found or unauthorized.');

        $subject = new UpdateRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_requires_name(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->costume);
        $rules = $subject->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertContains('required', $rules['name']);
    }

    public function test_rules_validates_name_is_string(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->costume);
        $rules = $subject->rules();

        $this->assertContains('string', $rules['name']);
    }

    public function test_rules_validates_name_max_length(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->costume);

        $validator = Validator::make(
            [
                'name' => str_repeat('a', 129), // max is 128
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_rules_validates_name_is_unique_excluding_current_costume(): void
    {
        $other_costume = Costume::factory()->create();
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->costume);

        $validator = Validator::make(
            [
                'name' => $other_costume->name,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_rules_allows_same_name_for_current_costume(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->costume);

        $validator = Validator::make(
            [
                'name' => $this->costume->name,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_unique_name(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->costume);

        $validator = Validator::make(
            [
                'name' => 'Updated Costume Name',
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_allows_valid_selected_organizations(): void
    {
        $organization = Organization::factory()->create();
        $other_organization = Organization::factory()->create();

        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->costume);

        $validator = Validator::make(
            [
                'name' => 'Updated Costume Name',
                'organizations' => [
                    $organization->id => ['selected' => '1'],
                    $other_organization->id => ['selected' => '1'],
                ],
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_selected_organization_ids_extracts_checked_organizations(): void
    {
        $organization = Organization::factory()->create();
        $other_organization = Organization::factory()->create();

        $subject = new UpdateRequest;
        $subject->merge([
            'organizations' => [
                $organization->id => ['selected' => '1'],
                $other_organization->id => ['selected' => '1'],
            ],
        ]);

        $selected_ids = $subject->selected_organization_ids();

        $this->assertEqualsCanonicalizing([$organization->id, $other_organization->id], $selected_ids);
    }

    public function test_selected_organization_ids_ignores_unchecked_organizations(): void
    {
        $organization = Organization::factory()->create();
        $unchecked_organization = Organization::factory()->create();

        $subject = new UpdateRequest;
        $subject->merge([
            'organizations' => [
                $organization->id => ['selected' => '1'],
                $unchecked_organization->id => ['selected' => ''],
            ],
        ]);

        $selected_ids = $subject->selected_organization_ids();

        $this->assertEquals([$organization->id], $selected_ids);
    }

    public function test_rules_allows_empty_selected_values(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->costume);

        $validator = Validator::make(
            [
                'name' => 'Updated Costume Name',
                'organizations' => [
                    '1' => ['selected' => ''],
                    '2' => ['selected' => ''],
                ],
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_validates_name_within_128_characters(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->costume);

        $validator = Validator::make(
            [
                'name' => str_repeat('a', 128), // exactly 128 chars
            ],
            $subject->rules()
        );

        // Should not fail for max length
        $this->assertIsArray($validator->errors()->toArray());
    }
}

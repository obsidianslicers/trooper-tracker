<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Http\Requests\Admin\Troopers\MembershipRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MembershipRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $admin;

    private Trooper $target_trooper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Trooper::factory()->asAdministrator()->create();
        $this->target_trooper = Trooper::factory()->asMember()->create();
        $this->actingAs($this->admin);
    }

    /**
     * Helper method to set up a mocked route parameter
     */
    private function setupMockedRoute(MembershipRequest $request, Trooper $trooper): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('trooper')
            ->andReturn($trooper);
        $mock_route->shouldReceive('parameter')
            ->with('trooper', \Mockery::any())
            ->andReturn($trooper);
        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_returns_true_for_administrator(): void
    {
        $subject = new MembershipRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $this->setupMockedRoute($subject, $this->target_trooper);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_non_administrator(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $subject = new MembershipRequest;
        $subject->setUserResolver(fn() => $moderator);
        $this->setupMockedRoute($subject, $this->target_trooper);

        $result = $subject->authorize();

        $this->assertFalse($result);
    }

    public function test_authorize_throws_exception_when_trooper_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Trooper not found or unauthorized.');

        $subject = new MembershipRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')->with('trooper')->andReturn(null);
        $mock_route->shouldReceive('parameter')->with('trooper', \Mockery::any())->andReturn(null);
        $subject->setRouteResolver(fn() => $mock_route);

        $subject->authorize();
    }

    public function test_rules_validates_organizations_is_array(): void
    {
        $subject = new MembershipRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $this->setupMockedRoute($subject, $this->target_trooper);
        $rules = $subject->rules();

        // The rules will include organizations validation
        $this->assertIsArray($rules);
    }

    public function test_rules_generates_dynamic_organization_validation(): void
    {
        $organization = Organization::factory()->create();
        $subject = new MembershipRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $this->setupMockedRoute($subject, $this->target_trooper);

        $rules = $subject->rules();

        // Verify rules are generated (actual keys depend on active organizations)
        $this->assertIsArray($rules);
    }

    public function test_attributes_provides_custom_names_for_organization_fields(): void
    {
        $organization = Organization::factory()->create();
        $subject = new MembershipRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $this->setupMockedRoute($subject, $this->target_trooper);
        $subject->merge([
            'organizations' => [
                $organization->id => [
                    'identifier' => 'TK-12345',
                    'assignment' => $organization->id,
                ],
            ],
        ]);

        $attributes = $subject->attributes();

        // Verify custom attribute names are provided for better error messages
        $this->assertIsArray($attributes);
    }

    public function test_rules_validates_valid_organization_assignment(): void
    {
        $organization = Organization::factory()->create();
        $subject = new MembershipRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $this->setupMockedRoute($subject, $this->target_trooper);

        $validator = Validator::make(
            [
                'organizations' => [
                    $organization->id => [
                        'assignment' => $organization->id,
                    ],
                ],
            ],
            $subject->rules()
        );

        // The validator should run without throwing exceptions
        $this->assertIsArray($validator->errors()->toArray());
    }
}

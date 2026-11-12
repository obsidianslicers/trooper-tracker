<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Http\Requests\Admin\Troopers\UpdateAuthorityRequest;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Mockery;
use Tests\TestCase;

class AuthorityRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $admin;

    private Trooper $target_trooper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Trooper::factory()->asSystemOwner()->create();
        $this->target_trooper = Trooper::factory()->asMember()->create();
        $this->actingAs($this->admin);
    }

    /**
     * Helper method to set up a mocked route parameter
     */
    private function setupMockedRoute(UpdateAuthorityRequest $request, Trooper $trooper): void
    {
        $mock_route = Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('trooper')
            ->andReturn($trooper);
        $mock_route->shouldReceive('parameter')
            ->with('trooper', Mockery::any())
            ->andReturn($trooper);
        $request->setRouteResolver(fn () => $mock_route);
    }

    public function test_authorize_returns_true_for_administrator(): void
    {
        $subject = new UpdateAuthorityRequest;
        $subject->setUserResolver(fn () => $this->admin);
        $this->setupMockedRoute($subject, $this->target_trooper);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_non_administrator(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $subject = new UpdateAuthorityRequest;
        $subject->setUserResolver(fn () => $moderator);
        $this->setupMockedRoute($subject, $this->target_trooper);

        $this->assertFalse($subject->authorize());
    }

    public function test_authorize_throws_exception_when_trooper_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Trooper not found or unauthorized.');

        $subject = new UpdateAuthorityRequest;
        $subject->setUserResolver(fn () => $this->admin);
        $mock_route = Mockery::mock();
        $mock_route->shouldReceive('parameter')->with('trooper')->andReturn(null);
        $mock_route->shouldReceive('parameter')->with('trooper', Mockery::any())->andReturn(null);
        $subject->setRouteResolver(fn () => $mock_route);

        $subject->authorize();
    }

    public function test_rules_membership_role_is_nullable(): void
    {
        $subject = new UpdateAuthorityRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::MEMBERSHIP_ROLE, $rules);
        $this->assertContains('nullable', $rules[Trooper::MEMBERSHIP_ROLE]);
    }

    public function test_rules_validates_membership_role_is_valid_enum(): void
    {
        $subject = new UpdateAuthorityRequest;

        $validator = Validator::make(
            [
                Trooper::MEMBERSHIP_ROLE => 'invalid-role',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::MEMBERSHIP_ROLE, $validator->errors()->toArray());
    }

    public function test_rules_accepts_member_role(): void
    {
        $subject = new UpdateAuthorityRequest;

        $validator = Validator::make(
            [
                Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_moderator_role(): void
    {
        $subject = new UpdateAuthorityRequest;

        $validator = Validator::make(
            [
                Trooper::MEMBERSHIP_ROLE => MembershipRole::MODERATOR->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_authorize_returns_true_for_staff_officer(): void
    {
        $staff_officer = Trooper::factory()->asStaffOfficer()->create();
        $subject = new UpdateAuthorityRequest;
        $subject->setUserResolver(fn () => $staff_officer);
        $this->setupMockedRoute($subject, $this->target_trooper);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_staff_officer_editing_administrator(): void
    {
        $staff_officer = Trooper::factory()->asStaffOfficer()->create();
        $subject = new UpdateAuthorityRequest;
        $subject->setUserResolver(fn () => $staff_officer);
        $this->setupMockedRoute($subject, $this->admin);

        $this->assertFalse($subject->authorize());
    }

    public function test_rules_rejects_administrator_role_from_staff_officer(): void
    {
        $staff_officer = Trooper::factory()->asStaffOfficer()->create();
        $subject = new UpdateAuthorityRequest;
        $subject->setUserResolver(fn () => $staff_officer);

        $validator = Validator::make(
            [Trooper::MEMBERSHIP_ROLE => MembershipRole::SYSTEM_OWNER->value],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
    }

    public function test_rules_accepts_administrator_role(): void
    {
        $subject = new UpdateAuthorityRequest;
        $subject->setUserResolver(fn () => $this->admin);

        $validator = Validator::make(
            [
                Trooper::MEMBERSHIP_ROLE => MembershipRole::SYSTEM_OWNER->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_validates_organizations_is_array(): void
    {
        $subject = new UpdateAuthorityRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey('organizations', $rules);
        $this->assertContains('array', $rules['organizations']);
    }

    public function test_rules_validates_is_moderator_is_boolean(): void
    {
        $subject = new UpdateAuthorityRequest;
        $rules = $subject->rules();

        $validator_path = 'organizations.*.'.TrooperAssignment::IS_MODERATOR;
        $this->assertArrayHasKey($validator_path, $rules);
        $this->assertContains('boolean', $rules[$validator_path]);
    }

    public function test_rules_allows_valid_authority_request(): void
    {
        $subject = new UpdateAuthorityRequest;

        $validator = Validator::make(
            [
                Trooper::MEMBERSHIP_ROLE => MembershipRole::MODERATOR->value,
                'organizations' => [
                    1 => [TrooperAssignment::IS_MODERATOR => true],
                    2 => [TrooperAssignment::IS_MODERATOR => false],
                ],
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_allows_null_membership_role(): void
    {
        $subject = new UpdateAuthorityRequest;

        $validator = Validator::make(
            [
                Trooper::MEMBERSHIP_ROLE => null,
                'organizations' => [],
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }
}

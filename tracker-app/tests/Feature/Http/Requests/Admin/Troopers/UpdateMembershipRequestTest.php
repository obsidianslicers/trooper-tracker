<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Http\Requests\Admin\Troopers\UpdateMembershipRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Mockery;
use Tests\TestCase;

class UpdateMembershipRequestTest extends TestCase
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

    private function makeSubject(Trooper $actor, ?Trooper $trooper, ?Organization $organization = null): UpdateMembershipRequest
    {
        $mock_route = Mockery::mock();
        $mock_route->shouldReceive('parameter')->with('trooper', Mockery::any())->andReturn($trooper);
        $mock_route->shouldReceive('parameter')->with('organization', Mockery::any())->andReturn($organization);

        $subject = new UpdateMembershipRequest;
        $subject->setUserResolver(fn () => $actor);
        $subject->setRouteResolver(fn () => $mock_route);

        return $subject;
    }

    public function test_authorize_returns_true_for_administrator(): void
    {
        $subject = $this->makeSubject($this->admin, $this->target_trooper);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_moderator_without_coverage(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $subject = $this->makeSubject($moderator, $this->target_trooper);

        $this->assertFalse($subject->authorize());
    }

    public function test_authorize_returns_false_for_member(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $subject = $this->makeSubject($member, $this->target_trooper);

        $this->assertFalse($subject->authorize());
    }

    public function test_authorize_throws_exception_when_trooper_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Trooper not found or unauthorized.');

        $this->makeSubject($this->admin, null)->authorize();
    }

    public function test_rules_accepts_valid_membership_status(): void
    {
        $organization = Organization::factory()->create();
        $subject = $this->makeSubject($this->admin, $this->target_trooper, $organization);

        $validator = Validator::make(
            ['membership_status' => MembershipStatus::RETIRED->value],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_rejects_invalid_membership_status(): void
    {
        $organization = Organization::factory()->create();
        $subject = $this->makeSubject($this->admin, $this->target_trooper, $organization);

        $validator = Validator::make(['membership_status' => 'invalid-status'], $subject->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_rules_requires_assignment_when_identifier_provided(): void
    {
        $organization = Organization::factory()->create();
        $subject = $this->makeSubject($this->admin, $this->target_trooper, $organization);
        $subject->merge(['identifier' => 'TK-12345']);

        $validator = Validator::make(['identifier' => 'TK-12345'], $subject->rules());

        $this->assertTrue($validator->errors()->has('assignment'));
    }

    public function test_attributes_names_fields_after_organization(): void
    {
        $organization = Organization::factory()->create();
        $subject = $this->makeSubject($this->admin, $this->target_trooper, $organization);

        $this->assertStringContainsString($organization->name, $subject->attributes()['identifier']);
    }
}

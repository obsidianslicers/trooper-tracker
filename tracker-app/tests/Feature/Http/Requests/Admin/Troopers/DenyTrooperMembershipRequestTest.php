<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Troopers;

use App\Http\Requests\Admin\Troopers\DenyTrooperMembershipRequest;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class DenyTrooperMembershipRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $admin;

    private Trooper $pending_trooper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Trooper::factory()->asAdministrator()->create();
        $this->pending_trooper = Trooper::factory()->asPending()->create();
    }

    public function test_authorize_returns_true_for_administrator(): void
    {
        $subject = new DenyTrooperMembershipRequest;
        $subject->setUserResolver(fn(): Trooper => $this->admin);
        $this->setupMockedRoute($subject, $this->pending_trooper);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_member(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $subject = new DenyTrooperMembershipRequest;
        $subject->setUserResolver(fn(): Trooper => $member);
        $this->setupMockedRoute($subject, $this->pending_trooper);

        $this->assertFalse($subject->authorize());
    }

    public function test_authorize_throws_exception_when_trooper_is_missing(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Trooper not found or unauthorized.');

        $subject = new DenyTrooperMembershipRequest;
        $subject->setUserResolver(fn(): Trooper => $this->admin);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_fail_when_trooper_is_not_pending(): void
    {
        $not_pending = Trooper::factory()->asActive()->create();
        $subject = new DenyTrooperMembershipRequest;
        $this->setupMockedRoute($subject, $not_pending);

        $validator = Validator::make(
            ['trooper' => $not_pending->id],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            ['The trooper must have a pending membership status.'],
            $validator->errors()->get('trooper')
        );
    }

    public function test_rules_pass_when_trooper_is_pending(): void
    {
        $subject = new DenyTrooperMembershipRequest;
        $this->setupMockedRoute($subject, $this->pending_trooper);

        $validator = Validator::make(
            ['trooper' => $this->pending_trooper->id],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    private function setupMockedRoute(
        DenyTrooperMembershipRequest $request,
        ?Trooper $trooper,
    ): void {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('trooper')
            ->andReturn($trooper);
        $mock_route->shouldReceive('parameter')
            ->with('trooper', \Mockery::any())
            ->andReturn($trooper);

        $request->setRouteResolver(fn() => $mock_route);
    }
}
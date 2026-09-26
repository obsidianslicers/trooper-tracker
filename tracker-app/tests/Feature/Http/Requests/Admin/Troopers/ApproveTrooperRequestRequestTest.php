<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Troopers;

use App\Http\Requests\Admin\Troopers\ApproveTrooperRequestRequest;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class ApproveTrooperRequestRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $admin;

    private TrooperRequest $pending_request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Trooper::factory()->asAdministrator()->create();
        $this->pending_request = TrooperRequest::factory()->asPending()->create();
    }

    public function test_authorize_returns_true_for_administrator(): void
    {
        $subject = new ApproveTrooperRequestRequest;
        $subject->setUserResolver(fn(): Trooper => $this->admin);
        $this->setupMockedRoute($subject, $this->pending_request);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_member(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $subject = new ApproveTrooperRequestRequest;
        $subject->setUserResolver(fn(): Trooper => $member);
        $this->setupMockedRoute($subject, $this->pending_request);

        $this->assertFalse($subject->authorize());
    }

    public function test_authorize_throws_exception_when_trooper_request_is_missing(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Trooper request not found or unauthorized.');

        $subject = new ApproveTrooperRequestRequest;
        $subject->setUserResolver(fn(): Trooper => $this->admin);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_fails_when_trooper_request_is_not_pending(): void
    {
        $not_pending = TrooperRequest::factory()->asApproved()->create();
        $subject = new ApproveTrooperRequestRequest;
        $this->setupMockedRoute($subject, $not_pending);

        $validator = Validator::make(
            ['trooper_request' => $not_pending->id],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            ['The trooper request must have a pending status.'],
            $validator->errors()->get('trooper_request')
        );
    }

    public function test_rules_pass_when_trooper_request_is_pending(): void
    {
        $subject = new ApproveTrooperRequestRequest;
        $this->setupMockedRoute($subject, $this->pending_request);

        $validator = Validator::make(
            ['trooper_request' => $this->pending_request->id],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    private function setupMockedRoute(
        ApproveTrooperRequestRequest $request,
        ?TrooperRequest $trooper_request,
    ): void {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('trooper_request')
            ->andReturn($trooper_request);
        $mock_route->shouldReceive('parameter')
            ->with('trooper_request', \Mockery::any())
            ->andReturn($trooper_request);

        $request->setRouteResolver(fn() => $mock_route);
    }
}

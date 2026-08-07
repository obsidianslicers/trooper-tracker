<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Http\Requests\Admin\Troopers\ProfileRequest;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProfileRequestTest extends TestCase
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
    private function setupMockedRoute(ProfileRequest $request, Trooper $trooper): void
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

    public function test_authorize_returns_true_for_admin(): void
    {
        $subject = new ProfileRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $this->setupMockedRoute($subject, $this->target_trooper);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_throws_exception_when_trooper_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Trooper not found or unauthorized.');

        $subject = new ProfileRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')->with('trooper')->andReturn(null);
        $mock_route->shouldReceive('parameter')->with('trooper', \Mockery::any())->andReturn(null);
        $subject->setRouteResolver(fn() => $mock_route);

        $subject->authorize();
    }

    public function test_rules_requires_legal_name(): void
    {
        $subject = new ProfileRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::LEGAL_NAME, $rules);
        $this->assertContains('required', $rules[Trooper::LEGAL_NAME]);
    }

    public function test_rules_requires_display_name(): void
    {
        $subject = new ProfileRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::DISPLAY_NAME, $rules);
        $this->assertContains('required', $rules[Trooper::DISPLAY_NAME]);
    }

    public function test_rules_requires_email(): void
    {
        $subject = new ProfileRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::EMAIL, $rules);
        $this->assertContains('required', $rules[Trooper::EMAIL]);
        $this->assertContains('email', $rules[Trooper::EMAIL]);
    }

    public function test_rules_phone_is_nullable(): void
    {
        $subject = new ProfileRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::PHONE, $rules);
        $this->assertContains('nullable', $rules[Trooper::PHONE]);
    }

    public function test_rules_membership_status_is_nullable(): void
    {
        $subject = new ProfileRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::MEMBERSHIP_STATUS, $rules);
        $this->assertContains('nullable', $rules[Trooper::MEMBERSHIP_STATUS]);
    }

    public function test_rules_validates_membership_status_is_valid_enum(): void
    {
        $subject = new ProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::EMAIL => 'test@example.com',
                Trooper::MEMBERSHIP_STATUS => 'invalid-status',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::MEMBERSHIP_STATUS, $validator->errors()->toArray());
    }

    public function test_rules_accepts_active_membership_status(): void
    {
        $subject = new ProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::EMAIL => 'test@example.com',
                Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_pending_membership_status(): void
    {
        $subject = new ProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::EMAIL => 'test@example.com',
                Trooper::MEMBERSHIP_STATUS => MembershipStatus::PENDING->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_retired_membership_status(): void
    {
        $subject = new ProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::EMAIL => 'test@example.com',
                Trooper::MEMBERSHIP_STATUS => MembershipStatus::RETIRED->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_validates_legal_name_max_length(): void
    {
        $subject = new ProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => str_repeat('a', 257),
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::EMAIL => 'test@example.com',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::LEGAL_NAME, $validator->errors()->toArray());
    }

    public function test_rules_validates_email_format(): void
    {
        $subject = new ProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::EMAIL => 'not-an-email',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::EMAIL, $validator->errors()->toArray());
    }

    public function test_rules_rejects_email_already_used_by_another_trooper(): void
    {
        $subject = new ProfileRequest;
        $this->setupMockedRoute($subject, $this->target_trooper);

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::EMAIL => $this->admin->email,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::EMAIL, $validator->errors()->toArray());
    }

    public function test_rules_accepts_troopers_own_unchanged_email(): void
    {
        $subject = new ProfileRequest;
        $this->setupMockedRoute($subject, $this->target_trooper);

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::EMAIL => $this->target_trooper->email,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }
}

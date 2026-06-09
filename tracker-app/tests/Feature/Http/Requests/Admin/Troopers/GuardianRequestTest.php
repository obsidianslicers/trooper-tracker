<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Troopers;

use App\Http\Requests\Admin\Troopers\GuardianRequest;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class GuardianRequestTest extends TestCase
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

    private function setupMockedRoute(GuardianRequest $request, ?Trooper $trooper): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')->with('trooper')->andReturn($trooper);
        $mock_route->shouldReceive('parameter')->with('trooper', \Mockery::any())->andReturn($trooper);
        $request->setRouteResolver(fn () => $mock_route);
    }

    public function test_authorize_returns_true_for_admin(): void
    {
        $subject = new GuardianRequest;
        $subject->setUserResolver(fn () => $this->admin);
        $this->setupMockedRoute($subject, $this->target_trooper);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_member(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $subject = new GuardianRequest;
        $subject->setUserResolver(fn () => $member);
        $this->setupMockedRoute($subject, $this->target_trooper);

        $this->assertFalse($subject->authorize());
    }

    public function test_authorize_throws_exception_when_trooper_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Trooper not found or unauthorized.');

        $subject = new GuardianRequest;
        $subject->setUserResolver(fn () => $this->admin);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_date_of_birth_is_nullable(): void
    {
        $subject = new GuardianRequest;

        $validator = Validator::make([], $subject->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_guardian_email_is_nullable_when_no_dob(): void
    {
        $subject = new GuardianRequest;

        $validator = Validator::make(['guardian_email' => null], $subject->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_both_null_passes_when_dob_is_null(): void
    {
        $subject = new GuardianRequest;

        $validator = Validator::make(
            [Trooper::DATE_OF_BIRTH => null, 'guardian_email' => null],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_guardian_email_is_required_when_trooper_is_minor(): void
    {
        $dob = Carbon::now()->subYears(16)->toDateString();
        $subject = new GuardianRequest;
        $subject->merge([Trooper::DATE_OF_BIRTH => $dob]);

        $validator = Validator::make([Trooper::DATE_OF_BIRTH => $dob], $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('guardian_email', $validator->errors()->toArray());
    }

    public function test_rules_guardian_email_is_nullable_when_trooper_is_adult(): void
    {
        $dob = Carbon::now()->subYears(20)->toDateString();
        $subject = new GuardianRequest;
        $subject->merge([Trooper::DATE_OF_BIRTH => $dob]);

        $guardian = Trooper::factory()->create();

        $validator = Validator::make(
            [Trooper::DATE_OF_BIRTH => $dob, 'guardian_email' => null],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_guardian_email_must_exist_in_troopers_table(): void
    {
        $dob = Carbon::now()->subYears(20)->toDateString();
        $subject = new GuardianRequest;
        $subject->merge([Trooper::DATE_OF_BIRTH => $dob]);

        $validator = Validator::make(
            [Trooper::DATE_OF_BIRTH => $dob, 'guardian_email' => 'nonexistent@example.com'],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('guardian_email', $validator->errors()->toArray());
    }

    public function test_rules_guardian_email_accepts_existing_trooper_email(): void
    {
        $dob = Carbon::now()->subYears(20)->toDateString();
        $guardian = Trooper::factory()->create();
        $subject = new GuardianRequest;
        $subject->merge([Trooper::DATE_OF_BIRTH => $dob]);

        $validator = Validator::make(
            [Trooper::DATE_OF_BIRTH => $dob, 'guardian_email' => $guardian->email],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Troopers;

use App\Http\Requests\Admin\Troopers\GuardianRequest;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    /**
     * Helper method to set up a mocked route parameter
     */
    private function setupMockedRoute(GuardianRequest $request, ?Trooper $trooper): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')->with('trooper')->andReturn($trooper);
        $mock_route->shouldReceive('parameter')->with('trooper', \Mockery::any())->andReturn($trooper);
        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_returns_true_for_administrator(): void
    {
        $subject = new GuardianRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $this->setupMockedRoute($subject, $this->target_trooper);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_member(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $subject = new GuardianRequest;
        $subject->setUserResolver(fn() => $member);
        $this->setupMockedRoute($subject, $this->target_trooper);

        $this->assertFalse($subject->authorize());
    }

    public function test_authorize_throws_exception_when_trooper_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Trooper not found or unauthorized.');

        $subject = new GuardianRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_require_guardian_email_for_minor_date_of_birth(): void
    {
        $subject = new GuardianRequest;
        $data = [
            Trooper::DATE_OF_BIRTH => now()->subYears(17)->toDateString(),
        ];
        $subject->merge($data);

        $validator = Validator::make($data, $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('guardian_email', $validator->errors()->toArray());
    }

    public function test_rules_do_not_require_guardian_email_for_exactly_eighteen(): void
    {
        $subject = new GuardianRequest;
        $data = [
            Trooper::DATE_OF_BIRTH => now()->subYears(18)->toDateString(),
        ];
        $subject->merge($data);

        $validator = Validator::make($data, $subject->rules());

        $this->assertFalse($validator->errors()->has('guardian_email'));
    }

    public function test_rules_do_not_require_guardian_email_when_date_of_birth_is_null(): void
    {
        $subject = new GuardianRequest;
        $data = [
            Trooper::DATE_OF_BIRTH => null,
        ];
        $subject->merge($data);

        $validator = Validator::make($data, $subject->rules());

        $this->assertFalse($validator->errors()->has('guardian_email'));
    }

    public function test_rules_accept_guardian_email_when_guardian_date_of_birth_is_null(): void
    {
        $guardian = Trooper::factory()->asMember()->create([
            Trooper::DATE_OF_BIRTH => null,
        ]);
        $subject = new GuardianRequest;
        $data = [
            Trooper::DATE_OF_BIRTH => now()->subYears(15)->toDateString(),
            'guardian_email' => $guardian->{Trooper::EMAIL},
        ];
        $subject->merge($data);

        $validator = Validator::make($data, $subject->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accept_guardian_email_when_guardian_is_adult(): void
    {
        $guardian = Trooper::factory()->asMember()->create([
            Trooper::DATE_OF_BIRTH => now()->subYears(25)->toDateString(),
        ]);
        $subject = new GuardianRequest;
        $data = [
            Trooper::DATE_OF_BIRTH => now()->subYears(14)->toDateString(),
            'guardian_email' => $guardian->{Trooper::EMAIL},
        ];
        $subject->merge($data);

        $validator = Validator::make($data, $subject->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_reject_guardian_email_when_guardian_is_minor(): void
    {
        $guardian = Trooper::factory()->asMember()->create([
            Trooper::DATE_OF_BIRTH => now()->subYears(16)->toDateString(),
        ]);
        $subject = new GuardianRequest;
        $data = [
            Trooper::DATE_OF_BIRTH => now()->subYears(15)->toDateString(),
            'guardian_email' => $guardian->{Trooper::EMAIL},
        ];
        $subject->merge($data);

        $validator = Validator::make($data, $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('guardian_email', $validator->errors()->toArray());
    }
}

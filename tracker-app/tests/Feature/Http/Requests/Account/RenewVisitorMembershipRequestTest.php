<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Account;

use App\Http\Requests\Account\RenewVisitorMembershipRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class RenewVisitorMembershipRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_allows_the_request(): void
    {
        $subject = new RenewVisitorMembershipRequest();

        $this->assertTrue($subject->authorize());
    }

    public function test_validation_data_uses_authenticated_trooper_instead_of_submitted_value(): void
    {
        $route_trooper = Trooper::factory()->asVisitor()->create([
            Trooper::VISITOR_EXPIRES_AT => now()->subDay(),
        ]);
        $submitted_trooper = Trooper::factory()->asVisitor()->create([
            Trooper::VISITOR_EXPIRES_AT => now()->addDay(),
        ]);
        $subject = new RenewVisitorMembershipRequest();
        $subject->merge(['trooper' => $submitted_trooper->id]);
        $this->setAuthenticatedTrooper($subject, $route_trooper);

        $this->assertSame($route_trooper, $subject->validationData()['trooper']);
    }

    public function test_rules_pass_for_authenticated_trooper_with_expired_visitor_membership(): void
    {
        $trooper = Trooper::factory()->asVisitor()->create([
            Trooper::VISITOR_EXPIRES_AT => now()->subDay(),
        ]);
        $subject = new RenewVisitorMembershipRequest();
        $this->setAuthenticatedTrooper($subject, $trooper);

        $validator = Validator::make($subject->validationData(), $subject->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_fail_for_authenticated_trooper_with_unexpired_visitor_membership(): void
    {
        $trooper = Trooper::factory()->asVisitor()->create([
            Trooper::VISITOR_EXPIRES_AT => now()->addDay(),
        ]);
        $subject = new RenewVisitorMembershipRequest();
        $this->setAuthenticatedTrooper($subject, $trooper);

        $validator = Validator::make($subject->validationData(), $subject->rules());

        $this->assertSame(
            'The trooper must have an expired visitor membership.',
            $validator->errors()->first('trooper'),
        );
    }

    private function setAuthenticatedTrooper(
        RenewVisitorMembershipRequest $request,
        Trooper $trooper,
    ): void {
        $request->setUserResolver(fn(): Trooper => $trooper);
    }
}
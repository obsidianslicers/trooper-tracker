<?php

declare(strict_types=1);

namespace Tests\Feature\Rules\Account;

use App\Enums\MembershipRole;
use App\Models\Trooper;
use App\Rules\Account\ExpiredVisitorMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class ExpiredVisitorMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_for_expired_visitor_trooper(): void
    {
        $trooper = Trooper::factory()->asVisitor()->create([
            Trooper::VISITOR_EXPIRES_AT => now()->subDay(),
        ]);

        $validator = Validator::make(
            ['trooper' => $trooper],
            ['trooper' => [new ExpiredVisitorMembership()]],
        );

        $this->assertTrue($validator->passes());
    }

    public function test_passes_for_expired_visitor_trooper_id(): void
    {
        $trooper = Trooper::factory()->asVisitor()->create([
            Trooper::VISITOR_EXPIRES_AT => now()->subDay(),
        ]);

        $validator = Validator::make(
            ['trooper' => $trooper->id],
            ['trooper' => [new ExpiredVisitorMembership()]],
        );

        $this->assertTrue($validator->passes());
    }

    public function test_fails_for_non_visitor_trooper(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
            Trooper::VISITOR_EXPIRES_AT => now()->subDay(),
        ]);

        $validator = Validator::make(
            ['trooper' => $trooper],
            ['trooper' => [new ExpiredVisitorMembership()]],
        );

        $this->assertSame(
            'The trooper must have a visitor membership.',
            $validator->errors()->first('trooper'),
        );
    }

    public function test_fails_for_unexpired_visitor_membership(): void
    {
        $trooper = Trooper::factory()->asVisitor()->create([
            Trooper::VISITOR_EXPIRES_AT => now()->addDay(),
        ]);

        $validator = Validator::make(
            ['trooper' => $trooper],
            ['trooper' => [new ExpiredVisitorMembership()]],
        );

        $this->assertSame(
            'The trooper must have an expired visitor membership.',
            $validator->errors()->first('trooper'),
        );
    }

    public function test_fails_when_visitor_membership_has_no_expiration_date(): void
    {
        $trooper = Trooper::factory()->asVisitor()->create([
            Trooper::VISITOR_EXPIRES_AT => null,
        ]);

        $validator = Validator::make(
            ['trooper' => $trooper],
            ['trooper' => [new ExpiredVisitorMembership()]],
        );

        $this->assertSame(
            'The trooper must have an expired visitor membership.',
            $validator->errors()->first('trooper'),
        );
    }

    public function test_fails_when_trooper_cannot_be_found(): void
    {
        $validator = Validator::make(
            ['trooper' => PHP_INT_MAX],
            ['trooper' => [new ExpiredVisitorMembership()]],
        );

        $this->assertSame(
            'The specified trooper could not be found.',
            $validator->errors()->first('trooper'),
        );
    }
}
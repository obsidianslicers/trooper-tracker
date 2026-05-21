<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Features\Troopers\Queries\GetExpiredVisitorsQuery;
use App\Features\Troopers\Queries\GetExpiredVisitorsQueryHandler;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetExpiredVisitorsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function invoke(): \Illuminate\Support\Collection
    {
        return app(GetExpiredVisitorsQueryHandler::class)(new GetExpiredVisitorsQuery());
    }

    public function test_returns_active_visitors_past_expiry_without_notification(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE    => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS  => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->subMonth(),
            Trooper::VISITOR_NOTIFIED_AT => null,
        ]);

        $result = $this->invoke();

        $this->assertTrue($result->contains('id', $trooper->id));
    }

    public function test_excludes_already_notified_visitors(): void
    {
        Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE    => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS  => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->subMonth(),
            Trooper::VISITOR_NOTIFIED_AT => now()->subWeek(),
        ]);

        $result = $this->invoke();

        $this->assertEmpty($result);
    }

    public function test_excludes_visitors_not_yet_expired(): void
    {
        Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE    => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS  => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->addMonths(3),
            Trooper::VISITOR_NOTIFIED_AT => null,
        ]);

        $result = $this->invoke();

        $this->assertEmpty($result);
    }

    public function test_excludes_non_visitor_roles(): void
    {
        Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE    => MembershipRole::MEMBER,
            Trooper::MEMBERSHIP_STATUS  => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->subMonth(),
            Trooper::VISITOR_NOTIFIED_AT => null,
        ]);

        $result = $this->invoke();

        $this->assertEmpty($result);
    }

    public function test_excludes_inactive_visitors(): void
    {
        Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE    => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS  => MembershipStatus::PENDING,
            Trooper::VISITOR_EXPIRES_AT => now()->subMonth(),
            Trooper::VISITOR_NOTIFIED_AT => null,
        ]);

        $result = $this->invoke();

        $this->assertEmpty($result);
    }
}

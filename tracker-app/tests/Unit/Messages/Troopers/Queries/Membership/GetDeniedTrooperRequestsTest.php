<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries\Membership;

use App\Messages\Troopers\Queries\Membership\GetDeniedTrooperRequests;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetDeniedTrooperRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_only_denied_requests_for_the_given_trooper_in_updated_order(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $other_trooper = Trooper::factory()->asActive()->create();

        $newer_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->asDenied()
            ->create([
                TrooperRequest::UPDATED_AT => now()->subDay(),
            ]);

        $older_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->asDenied()
            ->create([
                TrooperRequest::UPDATED_AT => now()->subDays(2),
            ]);

        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->asPending()
            ->create();

        TrooperRequest::factory()
            ->forTrooper($other_trooper)
            ->asDenied()
            ->create();

        $subject = new GetDeniedTrooperRequests($trooper);

        $result = $subject->handle();

        $this->assertCount(2, $result);
        $this->assertSame(
            [$newer_request->id, $older_request->id],
            $result->pluck('id')->all(),
        );
        $this->assertTrue($result->every(fn (TrooperRequest $request): bool => $request->trooper_id === $trooper->id));
    }
}

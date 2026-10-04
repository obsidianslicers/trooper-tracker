<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account\Commands;

use App\Enums\MembershipStatus;
use App\Jobs\SendTrooperRegisteredNotificationsJob;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class RenewVisitorMembershipControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_renews_expired_visitor_membership_and_redirects_to_thank_you(): void
    {
        Queue::fake();

        $trooper = Trooper::factory()->asVisitor()->create([
            Trooper::VISITOR_EXPIRES_AT => now()->subDay(),
        ]);

        $response = $this->actingAs($trooper)->post(
            route('account.renew-visitor-membership'),
        );

        $response->assertRedirect(route('auth.thank-you'));

        $this->assertDatabaseHas($trooper->getTable(), [
            Trooper::ID => $trooper->id,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::PENDING->value,
        ]);

        Queue::assertPushed(SendTrooperRegisteredNotificationsJob::class);
    }

    public function test_post_does_not_renew_visitor_with_unexpired_membership(): void
    {
        Queue::fake();

        $trooper = Trooper::factory()->asVisitor()->create([
            Trooper::VISITOR_EXPIRES_AT => now()->addDay(),
        ]);

        $response = $this->from(route('account.renew-visitor'))
            ->actingAs($trooper)
            ->post(route('account.renew-visitor-membership'));

        $response->assertRedirect(route('account.renew-visitor'));
        $response->assertSessionHasErrors('trooper');

        $this->assertDatabaseHas($trooper->getTable(), [
            Trooper::ID => $trooper->id,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
        ]);

        Queue::assertNothingPushed();
    }
}
<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Event;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Notifications\Admin\TrooperRegisteredNotification;
use App\Notifications\Admin\TrooperRequestSubmittedNotification;
use App\Notifications\Admin\TrooperResubmittedNotification;
use App\Notifications\BaseNotification;
use App\Notifications\Events\BroadcastEventMessageNotification;
use App\Notifications\Troopers\MembershipApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The bell inbox and the mobile app open a notification's url as a path on the
 * tracker, so each one must land on the page it names rather than a 404.
 */
class NotificationLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_urls_resolve_to_their_pages(): void
    {
        $notifiable = Trooper::factory()->create();
        $event = Event::factory()->create();
        $trooper_request = TrooperRequest::factory()->create();

        $expected = [
            'admin.troopers.approvals.index' => [
                new TrooperRegisteredNotification,
                new TrooperResubmittedNotification($notifiable),
                new TrooperRequestSubmittedNotification($trooper_request),
            ],
            'events.display' => [new BroadcastEventMessageNotification($event, 'Hello')],
            'account.index' => [new MembershipApprovedNotification],
        ];

        foreach ($expected as $route_name => $notifications)
        {
            foreach ($notifications as $notification)
            {
                $this->assertRouteName($route_name, $notification, $notifiable);
            }
        }
    }

    public function test_old_approvals_link_redirects_to_approvals_page(): void
    {
        $owner = Trooper::factory()->asSystemOwner()->create();

        $this->actingAs($owner)
            ->get('/admin/troopers/approvals')
            ->assertRedirect('/admin/troopers/approvals/index');
    }

    public function test_old_event_link_redirects_to_event_details(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $this->actingAs($trooper)
            ->get('/events/42')
            ->assertRedirect('/events/details/42');
    }

    public function test_old_profile_link_redirects_to_account(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $this->actingAs($trooper)
            ->get('/account/profile')
            ->assertRedirect('/account');
    }

    private function assertRouteName(
        string $route_name,
        BaseNotification $notification,
        Trooper $notifiable,
    ): void {
        $url = $notification->toArray($notifiable)['url'];

        $this->assertStringStartsWith('/', $url, $notification::class.' must link a path.');

        $route = Route::getRoutes()->match(Request::create($url, 'GET'));

        $this->assertSame($route_name, $route->getName(), $notification::class." links {$url}.");
    }
}

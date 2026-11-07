<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\MembershipStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\EventUpload;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Walks every admin route as an event coordinator, so a route added or changed later can't
 * quietly give event coordinators more than their own events.
 */
class EventCoordinatorRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $event_coordinator;

    private Organization $region;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->region = Organization::factory()->asRegion()->withNodePath('1:2:')->create();

        $this->event_coordinator = Trooper::factory()->asEventCoordinator()->withVerifiedEmail()->create();

        TrooperAssignment::factory()
            ->forTrooper($this->event_coordinator)
            ->forOrganization($this->region)
            ->asMember()
            ->create();
    }

    public function test_every_event_route_refuses_someone_elses_event_in_their_organization(): void
    {
        $parameters = $this->eventWithChildren(Trooper::factory()->asModerator()->create());

        foreach ($this->eventRoutes() as $route)
        {
            //  copying is allowed for events of their own organizations; the copy is theirs
            if (Str::endsWith($route->uri(), '/copy'))
            {
                continue;
            }

            $this->assertRefused($route, $parameters);
        }
    }

    public function test_every_event_route_refuses_an_event_outside_their_organizations(): void
    {
        $outside = Organization::factory()->asRegion()->withNodePath('1:4:')->create();
        $parameters = $this->eventWithChildren(Trooper::factory()->asModerator()->create(), $outside);

        foreach ($this->eventRoutes() as $route)
        {
            $this->assertRefused($route, $parameters);
        }
    }

    public function test_every_event_route_lets_a_coordinator_into_their_own_event(): void
    {
        $parameters = $this->eventWithChildren($this->event_coordinator);

        foreach ($this->eventRoutes() as $route)
        {
            foreach ($this->methods($route) as $method)
            {
                $status = $this->callRoute($method, $route, $parameters)->status();

                $this->assertNotContains(
                    $status,
                    [401, 403, 404, 500],
                    "{$method} {$route->uri()} answered {$status} for an event coordinator on their own event"
                );
            }
        }
    }

    public function test_every_other_admin_route_refuses_an_event_coordinator(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $route) => Str::startsWith((string) $route->getName(), 'admin.')
                && !Str::startsWith((string) $route->getName(), 'admin.events.'));

        $this->assertNotEmpty($routes);

        foreach ($routes as $route)
        {
            foreach ($this->methods($route) as $method)
            {
                $status = $this->callRoute($method, $route, [])->status();

                //  404 when route model binding finds nothing before the role check runs
                $this->assertContains(
                    $status,
                    [403, 404],
                    "{$method} {$route->uri()} answered {$status} for an event coordinator"
                );
            }
        }
    }

    public function test_an_inactive_coordinator_gets_nothing(): void
    {
        $this->event_coordinator->update([Trooper::MEMBERSHIP_STATUS => MembershipStatus::RESERVE]);

        $own = $this->eventWithChildren($this->event_coordinator);

        $this->actingAs($this->event_coordinator)->get(route('admin.events.list'))->assertForbidden();
        $this->actingAs($this->event_coordinator)->get(route('admin.events.create'))->assertForbidden();
        $this->actingAs($this->event_coordinator)->get(route('admin.events.update', $own))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function assertRefused(LaravelRoute $route, array $parameters): void
    {
        foreach ($this->methods($route) as $method)
        {
            $this->assertSame(
                403,
                $this->callRoute($method, $route, $parameters)->status(),
                "{$method} {$route->uri()} should refuse an event coordinator on someone else's event"
            );
        }
    }

    /**
     * @return iterable<LaravelRoute>
     */
    private function eventRoutes(): iterable
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $route) => Str::startsWith((string) $route->getName(), 'admin.events.')
                && in_array('event', $route->parameterNames(), true));

        //  sanity: the admin event pages are all here (update, shifts, troopers, uploads, ...)
        $this->assertGreaterThan(15, $routes->count());

        return $routes;
    }

    /**
     * @return list<string>
     */
    private function methods(LaravelRoute $route): array
    {
        return array_values(array_diff($route->methods(), ['HEAD']));
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function callRoute(string $method, LaravelRoute $route, array $parameters): TestResponse
    {
        //  from the route's own path: a few admin routes share a name, so route() can't be used
        $uri = '/'.preg_replace_callback(
            '/\{(\w+)\??\}/',
            fn (array $match) => (string) ($parameters[$match[1]] ?? 1),
            $route->uri()
        );

        //  query input some pickers need (e.g. the roster's costume picker takes trooper_id)
        $query = isset($parameters['trooper_id']) ? ['trooper_id' => $parameters['trooper_id']] : [];

        return $this->actingAs($this->event_coordinator)
            ->withHeader('HX-Request', 'true')
            ->call($method, $uri, $query);
    }

    /**
     * An event (in the event coordinator's region unless given) with one of everything the event
     * routes take.
     *
     * @return array<string, mixed>
     */
    private function eventWithChildren(Trooper $creator, ?Organization $organization = null): array
    {
        $event = Event::factory()
            ->withOrganization($organization ?? $this->region)
            ->withCreatedByTrooper($creator)
            ->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_shift_station = EventShiftStation::factory()->forEventShift($event_shift)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper(Trooper::factory()->asActive()->create())
            ->create();
        $event_upload = EventUpload::factory()->create([
            EventUpload::EVENT_ID => $event->id,
            EventUpload::TROOPER_ID => $creator->id,
        ]);

        return [
            'event' => $event->id,
            'event_shift' => $event_shift->id,
            'event_shift_station' => $event_shift_station->id,
            'event_trooper' => $event_trooper->id,
            'event_upload' => $event_upload->id,
            'trooper_id' => $event_trooper->trooper_id,
        ];
    }
}

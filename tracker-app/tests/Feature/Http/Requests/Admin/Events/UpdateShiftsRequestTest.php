<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Events;

use App\Enums\EventStatus;
use App\Http\Requests\Admin\Events\UpdateShiftsRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateShiftsRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $moderator;

    private Organization $organization;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->moderator = Trooper::factory()->asModerator()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $this->moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $this->organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $this->event = Event::factory()->create([
            Event::ORGANIZATION_ID => $this->organization->id,
        ]);

        $this->actingAs($this->moderator);
    }

    /**
     * Helper method to set up a mocked route parameter
     */
    private function setupMockedRoute(UpdateShiftsRequest $request, ?Event $event): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('event')
            ->andReturn($event);
        $mock_route->shouldReceive('parameter')
            ->with('event', \Mockery::any())
            ->andReturn($event);
        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_returns_true_for_moderator(): void
    {
        $subject = new UpdateShiftsRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $this->setupMockedRoute($subject, $this->event);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_throws_exception_when_event_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Event not found or unauthorized.');

        $subject = new UpdateShiftsRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_requires_shift_date(): void
    {
        $subject = new UpdateShiftsRequest;
        $this->setupMockedRoute($subject, $this->event);
        $rules = $subject->rules();

        $this->assertArrayHasKey('shifts.*.date', $rules);
        $this->assertContains('required', $rules['shifts.*.date']);
    }

    public function test_rules_validates_shift_date_is_date(): void
    {
        $subject = new UpdateShiftsRequest;
        $this->setupMockedRoute($subject, $this->event);
        $rules = $subject->rules();

        $this->assertContains('date', $rules['shifts.*.date']);
    }

    public function test_rules_requires_starts_at(): void
    {
        $subject = new UpdateShiftsRequest;
        $this->setupMockedRoute($subject, $this->event);
        $rules = $subject->rules();

        $this->assertArrayHasKey('shifts.*.starts_at', $rules);
        $this->assertContains('required', $rules['shifts.*.starts_at']);
    }

    public function test_rules_validates_starts_at_format(): void
    {
        $subject = new UpdateShiftsRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'shifts' => [
                    [
                        'date' => '2024-01-15',
                        'starts_at' => 'invalid-time',
                        'ends_at' => '14:00',
                    ],
                ],
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('shifts.0.starts_at', $validator->errors()->toArray());
    }

    public function test_rules_requires_ends_at(): void
    {
        $subject = new UpdateShiftsRequest;
        $this->setupMockedRoute($subject, $this->event);
        $rules = $subject->rules();

        $this->assertArrayHasKey('shifts.*.ends_at', $rules);
        $this->assertContains('required', $rules['shifts.*.ends_at']);
    }

    public function test_rules_validates_ends_at_format(): void
    {
        $subject = new UpdateShiftsRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'shifts' => [
                    [
                        'date' => '2024-01-15',
                        'starts_at' => '10:00',
                        'ends_at' => 'invalid-time',
                    ],
                ],
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('shifts.0.ends_at', $validator->errors()->toArray());
    }

    public function test_rules_validates_status_is_nullable(): void
    {
        $subject = new UpdateShiftsRequest;
        $this->setupMockedRoute($subject, $this->event);
        $rules = $subject->rules();

        $this->assertArrayHasKey('shifts.*.status', $rules);
        $this->assertContains('nullable', $rules['shifts.*.status']);
    }

    public function test_rules_validates_status_is_valid_enum(): void
    {
        $subject = new UpdateShiftsRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'shifts' => [
                    [
                        'date' => '2024-01-15',
                        'starts_at' => '10:00',
                        'ends_at' => '14:00',
                        'status' => 'invalid-status',
                    ],
                ],
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
    }

    public function test_rules_accepts_valid_shift_data(): void
    {
        $subject = new UpdateShiftsRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'shifts' => [
                    [
                        'date' => '2024-01-15',
                        'starts_at' => '10:00',
                        'ends_at' => '14:00',
                        'status' => EventStatus::OPEN->value,
                    ],
                ],
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_multiple_shifts(): void
    {
        $subject = new UpdateShiftsRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'shifts' => [
                    [
                        'date' => '2024-01-15',
                        'starts_at' => '10:00',
                        'ends_at' => '14:00',
                        'status' => EventStatus::OPEN->value,
                    ],
                    [
                        'date' => '2024-01-16',
                        'starts_at' => '15:00',
                        'ends_at' => '18:00',
                        'status' => EventStatus::CLOSED->value,
                    ],
                ],
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }
}

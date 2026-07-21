<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\UpdateEventShiftStationsCommand;
use App\Features\Events\Commands\UpdateEventShiftStationsCommandHandler;
use App\Http\Requests\Admin\Events\UpdateShiftsRequest;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Every station must always have a required positive integer capacity.
 *
 * Stations are never unlimited and the limit is never null: the request
 * validation rejects missing or invalid limits, the domain handler refuses
 * to persist them, the factory always provides one, and the database column
 * is non-nullable (with a CHECK >= 1 constraint on MySQL).
 */
class EventShiftStationLimitValidationTest extends TestCase
{
    use RefreshDatabase;

    private function stationPayload(array $station): array
    {
        return [
            'shifts' => [
                1 => [
                    'date' => '2026-08-01',
                    'starts_at' => '10:00',
                    'ends_at' => '12:00',
                    'stations' => [1 => $station],
                ],
            ],
        ];
    }

    private function validationFails(array $station): bool
    {
        $payload = $this->stationPayload($station);

        $request = new UpdateShiftsRequest;
        $request->merge($payload);

        $validator = Validator::make($payload, $request->rules());
        $request->withValidator($validator);

        return $validator->fails();
    }

    public function test_station_validation_rejects_invalid_limits(): void
    {
        $invalid_limits = [
            'missing' => ['name' => 'Photo Booth'],
            'null' => ['name' => 'Photo Booth', 'troopers_allowed' => null],
            'empty string' => ['name' => 'Photo Booth', 'troopers_allowed' => ''],
            'zero' => ['name' => 'Photo Booth', 'troopers_allowed' => 0],
            'negative' => ['name' => 'Photo Booth', 'troopers_allowed' => -1],
            'non-numeric' => ['name' => 'Photo Booth', 'troopers_allowed' => 'abc'],
            'decimal' => ['name' => 'Photo Booth', 'troopers_allowed' => 1.5],
        ];

        foreach ($invalid_limits as $label => $station)
        {
            $this->assertTrue(
                $this->validationFails($station),
                "Station with {$label} limit must fail validation"
            );
        }
    }

    public function test_station_validation_accepts_positive_integer_limit(): void
    {
        $this->assertFalse(
            $this->validationFails(['name' => 'Photo Booth', 'troopers_allowed' => 2]),
            'Station with a positive integer limit must pass validation'
        );
    }

    public function test_station_factory_always_provides_positive_limit(): void
    {
        $shift = EventShift::factory()->create();

        $station = EventShiftStation::factory()->forEventShift($shift)->create();

        $this->assertIsInt($station->troopers_allowed, 'Factory limit is always a number');
        $this->assertGreaterThan(0, $station->troopers_allowed, 'Factory limit is always positive');
    }

    public function test_database_rejects_null_station_limit(): void
    {
        $shift = EventShift::factory()->create();

        $this->expectException(QueryException::class);

        EventShiftStation::factory()
            ->forEventShift($shift)
            ->create([EventShiftStation::TROOPERS_ALLOWED => null]);
    }

    public function test_handler_rejects_station_update_without_positive_limit(): void
    {
        Notification::fake();

        $event = Event::factory()->create([Event::TROOPERS_ALLOWED => null]);
        $shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()
            ->forEventShift($shift)
            ->state([EventShiftStation::TROOPERS_ALLOWED => 2])
            ->create();

        $going_cet = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper(Trooper::factory()->create())
            ->forEventShiftStation($station)
            ->asGoing()
            ->state([
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(2),
            ])
            ->create();

        $standby_cet = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper(Trooper::factory()->create())
            ->forEventShiftStation($station)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinute(),
            ]);

        $subject = app(UpdateEventShiftStationsCommandHandler::class);

        try
        {
            $subject(new UpdateEventShiftStationsCommand($shift, [
                $station->id => ['name' => 'Photo Booth', 'troopers_allowed' => null],
            ]));

            $this->fail('Handler must reject a station update without a positive limit');
        }
        catch (InvalidArgumentException)
        {
            //  expected
        }

        $this->assertSame(
            2,
            $station->fresh()->troopers_allowed,
            'Invalid update leaves the existing limit unchanged'
        );
        $this->assertSame(
            EventTrooperStatus::GOING,
            $going_cet->fresh()->status,
            'Invalid update does not reconcile the roster'
        );
        $this->assertSame(
            EventTrooperStatus::STAND_BY,
            $standby_cet->fresh()->status,
            'Invalid update does not promote standby troopers'
        );
    }
}

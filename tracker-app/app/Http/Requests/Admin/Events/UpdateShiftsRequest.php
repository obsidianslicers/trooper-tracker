<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use App\Enums\EventStatus;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Handles the validation for updating the organizations associated with an Event.
 */
class UpdateShiftsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * Checks if the user has permission to update the event specified in the route.
     *
     * @throws AuthorizationException if the event is not found
     */
    public function authorize(): bool
    {
        $event = $this->route('event');

        if ($event === null)
        {
            throw new AuthorizationException('Event not found or unauthorized.');
        }

        return $this->user()->can('update', $event);
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed> The validation rules for the request
     */
    public function rules(): array
    {
        $rules = [
            'shifts.*.date' => ['required', 'date'],
            'shifts.*.starts_at' => ['required', 'date_format:H:i'],
            'shifts.*.ends_at' => ['required', 'date_format:H:i'],
            'shifts.*.status' => ['nullable', 'in:'.EventStatus::toValidator()],
            'shifts.*.stations.*.name' => ['nullable', 'string', 'max:128'],
            'shifts.*.stations.*.troopers_allowed' => ['nullable', 'integer', 'min:1', 'max:999'],
            'shifts.*.stations.*.sequence' => ['nullable', 'integer', 'min:0', 'max:9999'],

        ];

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateUniqueShiftStartTimes($validator);

            foreach ($this->input('shifts', []) as $key => $shift)
            {
                foreach ($shift['stations'] ?? [] as $station_key => $station)
                {
                    $has_name = !empty(trim((string) ($station['name'] ?? '')));
                    $has_count = !empty($station['troopers_allowed'] ?? null);

                    if ($has_name xor $has_count)
                    {
                        $validator->errors()->add(
                            "shifts.{$key}.stations.{$station_key}.name",
                            'Station name and requested count are both required.',
                        );
                    }
                }

                $this->validateShiftTimes($validator, $shift, $key);
                $this->validateClosedShiftStatus($validator, $shift, $key);
            }
        });
    }

    private function validateUniqueShiftStartTimes(Validator $validator): void
    {
        $seen_shift_start_times = [];

        foreach ($this->input('shifts', []) as $key => $shift)
        {
            if (!is_array($shift) ||
                $validator->errors()->has("shifts.{$key}.date") ||
                $validator->errors()->has("shifts.{$key}.starts_at") ||
                empty($shift['date']) ||
                empty($shift['starts_at']))
            {
                continue;
            }

            try
            {
                $shift_date = Carbon::parse((string) $shift['date'])->toDateString();
                $shift_starts_at = Carbon::createFromFormat('H:i', (string) $shift['starts_at'])
                    ->format('H:i');
            }
            catch (\Throwable)
            {
                continue;
            }

            $shift_key = $shift_date.'|'.$shift_starts_at;

            if (array_key_exists($shift_key, $seen_shift_start_times))
            {
                $validator->errors()->add(
                    "shifts.{$key}.starts_at",
                    'Shift date and start time must be unique.',
                );

                continue;
            }

            $seen_shift_start_times[$shift_key] = $key;
        }
    }

    /**
     * @param  array<string, mixed>  $shift
     */
    private function validateShiftTimes(Validator $validator, array $shift, int|string $key): void
    {
        if ($validator->errors()->has("shifts.{$key}.starts_at") ||
            $validator->errors()->has("shifts.{$key}.ends_at") ||
            empty($shift['starts_at']) ||
            empty($shift['ends_at']))
        {
            return;
        }

        $shift_starts_at = Carbon::createFromFormat('H:i', (string) $shift['starts_at']);
        $shift_ends_at = Carbon::createFromFormat('H:i', (string) $shift['ends_at']);

        if ($shift_ends_at->lessThanOrEqualTo($shift_starts_at))
        {
            $validator->errors()->add(
                "shifts.{$key}.ends_at",
                'End time must be after start time.',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $shift
     */
    private function validateClosedShiftStatus(Validator $validator, array $shift, int|string $key): void
    {
        if (empty($shift['status']) ||
            $shift['status'] !== EventStatus::CLOSED->value ||
            $validator->errors()->has("shifts.{$key}.date") ||
            $validator->errors()->has("shifts.{$key}.ends_at") ||
            empty($shift['date']) ||
            empty($shift['ends_at']))
        {
            return;
        }

        $shift_ends_at = Carbon::parse($shift['date'].' '.$shift['ends_at']);

        if ($shift_ends_at->isFuture())
        {
            $validator->errors()->add(
                "shifts.{$key}.status",
                'Cannot close a shift that has not yet ended ('.$shift_ends_at->format('M j, Y g:i A').').',
            );
        }
    }
}

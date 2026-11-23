<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Enums\FlashType;
use App\Enums\SignUpChangeOutcome;
use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Http\Controllers\PageController;
use App\Http\Requests\Events\Commands\UpdateTrooperSignUpRequest;
use App\Messages\Events\Commands\ChangeEventTrooperBackupCostume;
use App\Messages\Events\Commands\ChangeEventTrooperCostume;
use App\Messages\Events\Commands\ChangeEventTrooperOrganization;
use App\Messages\Events\Commands\ChangeEventTrooperStation;
use App\Messages\Events\Commands\ChangeEventTrooperStatus;
use App\Messages\Events\Commands\ReinstateEventTrooper;
use App\Models\EventTrooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Changes a sign-up from the event page: its plans, club, station, costume, or cancelling
 * and signing up again. Each change goes back to the page with a toast.
 */
class UpdateTrooperSignUpController extends PageController
{
    use AnswersWithEventPage;

    public function __invoke(UpdateTrooperSignUpRequest $request, EventTrooper $event_trooper): RedirectResponse|Response
    {
        try
        {
            $request->validateInputs();
        }
        catch (ValidationException $exception)
        {
            //  the page's pickers save one at a time, so the message is a toast, not field errors
            return $this->backToEvent(
                $event_trooper->event_shift->event,
                collect($exception->errors())->flatten()->first(),
                FlashType::DANGER,
            );
        }

        return match (true)
        {
            $request->has('status') => $this->changeStatus($request, $event_trooper),
            $request->has('organization_id') => $this->changeOrganization($request, $event_trooper),
            $request->has('event_shift_station_id') => $this->changeStation($request, $event_trooper),
            $request->has('reinstate') => $this->answer(
                $event_trooper,
                ReinstateEventTrooper::call(event_trooper: $event_trooper, signed_up_by_trooper: $request->user()),
                __('events.Signed up again.'),
            ),
            $request->has('costume_id') => $this->answer($event_trooper, ChangeEventTrooperCostume::call(
                event_trooper: $event_trooper,
                costume_id: $this->optionalString($request->validated('costume_id')),
                changed_by_trooper: $request->user(),
            )),
            $request->has('backup_costume_id') => $this->changeBackupCostume($request, $event_trooper),
            default => $this->backToEvent($event_trooper->event_shift->event),
        };
    }

    private function changeStatus(UpdateTrooperSignUpRequest $request, EventTrooper $event_trooper): RedirectResponse|Response
    {
        $status = $request->validated('status');
        $previous_status = $event_trooper->status;

        $outcome = ChangeEventTrooperStatus::call(
            event_trooper: $event_trooper,
            status: $status,
            changed_by_trooper: $request->user(),
        );

        return $this->answer($event_trooper, $outcome, match (true)
        {
            $status === EventTrooperStatus::CANCELLED->value => __('events.Sign-up cancelled.'),
            $previous_status === EventTrooperStatus::CANCELLED => __('events.Signed up again.'),
            default => __('events.Saved.'),
        });
    }

    private function changeOrganization(
        UpdateTrooperSignUpRequest $request,
        EventTrooper $event_trooper,
    ): RedirectResponse|Response {
        $outcome = ChangeEventTrooperOrganization::call(
            event_trooper: $event_trooper,
            organization_id: $this->optionalId($request->validated('organization_id')),
            changed_by_trooper: $request->user(),
        );

        return $this->answer($event_trooper, $outcome);
    }

    private function changeStation(UpdateTrooperSignUpRequest $request, EventTrooper $event_trooper): RedirectResponse|Response
    {
        $trooper = $request->user();

        if (!$event_trooper->canUpdateCostume($trooper) && !$trooper->can('update', $event_trooper->event_shift->event))
        {
            return response('Forbidden', 403);
        }

        ChangeEventTrooperStation::call(
            event_trooper: $event_trooper,
            event_shift_station_id: $this->optionalId($request->validated('event_shift_station_id')),
        );

        return $this->answer($event_trooper, SignUpChangeOutcome::SAVED);
    }

    private function changeBackupCostume(UpdateTrooperSignUpRequest $request, EventTrooper $event_trooper): RedirectResponse
    {
        ChangeEventTrooperBackupCostume::call(
            event_trooper: $event_trooper,
            backup_costume_id: $this->optionalId($request->validated('backup_costume_id')),
        );

        return $this->answer($event_trooper, SignUpChangeOutcome::SAVED);
    }

    private function answer(
        EventTrooper $event_trooper,
        SignUpChangeOutcome $outcome,
        ?string $saved = null,
    ): RedirectResponse|Response {
        $event = $event_trooper->event_shift->event;

        return match ($outcome)
        {
            SignUpChangeOutcome::FORBIDDEN => response('Forbidden', 403),
            SignUpChangeOutcome::STATUS_NOT_OFFERED => $this->backToEvent(
                $event,
                __('events.That status is not available for this event.'),
                FlashType::DANGER,
            ),
            SignUpChangeOutcome::STATION_FULL => $this->backToEvent($event, __('events.Station is full'), FlashType::DANGER),
            SignUpChangeOutcome::COSTUME_REQUIRED => $this->backToEvent(
                $event,
                __('events.Trooper must select a costume before being approved'),
                FlashType::DANGER,
            ),
            SignUpChangeOutcome::ORGANIZATION_FULL => $this->backToEvent(
                $event,
                __('events.That organization is already at capacity.'),
                FlashType::DANGER,
            ),
            SignUpChangeOutcome::SAVED => $this->saved($event_trooper, $saved ?? __('events.Saved.')),
        };
    }

    private function saved(EventTrooper $event_trooper, string $message): RedirectResponse
    {
        $message = match ($event_trooper->fresh()?->status)
        {
            EventTrooperStatus::STAND_BY => $message.' '.__('events.This sign-up is on the stand-by list.'),
            EventTrooperStatus::PENDING => $message.' '
                .__('events.Select a costume, or mark attending without one, to confirm your spot.'),
            default => $message,
        };

        return $this->backToEvent($event_trooper->event_shift->event, $message);
    }

    private function optionalId(mixed $value): ?int
    {
        return $value ? (int) $value : null;
    }

    private function optionalString(mixed $value): ?string
    {
        return $value !== null ? (string) $value : null;
    }
}

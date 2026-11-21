{{--
    "Your sign-up" for one shift, where the Sign Up form was: everything the trooper can change
    about their own place on the shift, in one spot above the roster. Their roster row below is
    read-only. The top-of-page summary (pages/events/inc/my-signup-summary.blade.php) links here.
--}}
@php($status = $event_trooper->status)
<div class="my-shift-signup"
     id="my-shift-signup-{{ $event_shift->id }}">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <span class="fw-semibold">
            <i class="fa fa-fw fa-user-check me-1"></i>
            {{ __('events.Your sign-up') }}
        </span>
        <x-sign-up-status :status="$status" class="my-shift-signup__status" />
    </div>
    @if($status === \App\Enums\EventTrooperStatus::PENDING)
        <div class="small mb-2">
            <i class="fa fa-fw fa-arrow-down me-1"></i>
            {{ __('events.Select a costume, or mark attending without one, to confirm your spot.') }}
        </div>
    @elseif($status === \App\Enums\EventTrooperStatus::STAND_BY && $event->status === \App\Enums\EventStatus::MANUAL_SELECTION)
        <div class="small text-muted mb-2">
            {{ __('events.Command staff picks who goes to this event. You\'re on the stand-by list until then.') }}
        </div>
    @elseif($status === \App\Enums\EventTrooperStatus::STAND_BY)
        <div class="small text-muted mb-2">
            {{ __('events.The shift is full. You\'re on the stand-by list and move up if a spot opens.') }}
        </div>
    @endif
    <div class="row g-3">
        <div class="col-12 col-md-6">
            @include('pages.events.inc.trooper-costume-controls', ['event_trooper' => $event_trooper, 'labelled' => true, 'read_only' => false])
        </div>
        <div class="col-12 col-md-6 text-md-end align-self-end my-shift-signup__actions">
            @include('pages.events.inc.trooper-status-controls', ['event_trooper' => $event_trooper, 'read_only' => false, 'show_status' => false])
        </div>
    </div>
</div>

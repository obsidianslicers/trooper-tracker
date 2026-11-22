{{-- One troop waiting for "did you make it?", with both answers: the bell and the notifications page. --}}
<div class="troop-to-confirm-event">
    <a class="d-block text-break"
       href="{{ route('events.display', ['event' => $event_trooper->event_shift->event_id]) }}#shift-{{ $event_trooper->event_shift_id }}">
        {{ $event_trooper->event_shift->event->name }}
    </a>
    <div class="small opacity-75">{{ $event_trooper->event_shift->time_display }}</div>
</div>
<form method="POST"
      action="{{ route('events.confirm-attendance', $event_trooper) }}"
      class="troop-answers d-flex gap-2 mt-2">
    @csrf
    <button type="submit"
            name="status"
            value="{{ \App\Enums\EventTrooperStatus::ATTENDED->value }}"
            class="btn btn-sm btn-outline-success">
        {{ __('events.Made It') }}
    </button>
    <button type="submit"
            name="status"
            value="{{ \App\Enums\EventTrooperStatus::UNABLE_TO_ATTEND->value }}"
            class="btn btn-sm btn-outline-danger">
        {{ __('events.Missed It') }}
    </button>
</form>

{{-- one row of a shift's roster; the viewer's own row is read-only, since they change their
     sign-up on the "Your sign-up" panel above the roster (pages/events/inc/my-shift-signup.blade.php) --}}
@php($is_my_row = $event_trooper->trooper_id === Auth::id())
<div class="row mb-3 trooper-status-{{ $event_trooper->status->value }} {{ $is_my_row ? 'roster-row--mine' : '' }}">
    <div class="col-7 col-md-4 order-1 order-md-1">
        <i class="d-none fa fa-fw fa-times pe-2 text-danger cancelled-icon"></i>
        <a href="{{ route('service-records.trooper', ['trooper' => $event_trooper->trooper->id]) }}">
            {{ $event_trooper->trooper->display_name }}
        </a>
        @if($is_my_row)
            <span class="badge text-bg-secondary ms-1">{{ __('events.You') }}</span>
        @endif
        <br/>
        <span class="text-muted small">{{ $event_trooper->trooper->legal_name }}</span>
        @if(($can_moderate ?? false) && $event_trooper->trooper->is_untrusted)
            <br />
            <span class="badge text-bg-warning">{{ __('events.Needs verification') }}</span>
        @endif
        @if($event_trooper->added_by_trooper_id > 0)
            <br />
            <i class="small text-muted">
                <i class="fa fa-fw fa-user-plus"></i>
                {{ $event_trooper->added_by_trooper->display_name }}
            </i>
        @endif
    </div>
    <div class="col-12 col-md-5 order-3 order-md-2">
        @include('pages.events.inc.trooper-costume-controls', ['event_trooper' => $event_trooper, 'read_only' => $is_my_row, 'labelled' => false])
    </div>
    <div class="col-12 col-md-3 order-2 order-md-3 text-md-end">
        <div class="ps-md-0">
            @include('pages.events.inc.trooper-status-controls', ['event_trooper' => $event_trooper, 'read_only' => $is_my_row])
        </div>
    </div>
</div>

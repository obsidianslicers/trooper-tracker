{{--
    One row of a shift's roster, read-only: who, their costume and their status. Rows the viewer
    may change (their own, friends they added, a child they're the guardian of) get an Edit link
    that opens the pickers under the row, in place, so the row keeps its spot in sign-up order.
    Which rows are open survives the shift being swapped after each change
    (resources/js/custom/roster-edit.js). Your own row opens by itself while it still needs a costume.
--}}
@php
    $viewer = Auth::user();
    $is_my_row = $event_trooper->trooper_id === $viewer->id;
    $can_edit = $event_trooper->status !== \App\Enums\EventTrooperStatus::CANCELLED
        && ($event_trooper->canUpdateCostume($viewer) || $event_trooper->canCancel($viewer));
    $opens_itself = $can_edit && $event_trooper->status === \App\Enums\EventTrooperStatus::PENDING
        && ($is_my_row || $event_trooper->added_by_trooper_id === $viewer->id);
@endphp
<div class="roster-row trooper-status-{{ $event_trooper->status->value }} {{ $is_my_row ? 'roster-row--mine' : '' }}"
     id="roster-row-{{ $event_trooper->id }}"
     @if($can_edit)
         x-data="Events.rosterRow({{ $event_trooper->id }}, @js($opens_itself))"
     @endif>
    <div class="roster-row__main">
        <span class="roster-row__number text-muted">{{ $roster_number ?? '' }}</span>
        <div class="roster-row__who">
            <a href="{{ route('service-records.trooper', ['trooper' => $event_trooper->trooper->id]) }}">
                {{ $event_trooper->trooper->display_name }}
            </a>
            @if($is_my_row)
                <span class="badge text-bg-secondary ms-1">{{ __('events.You') }}</span>
            @endif
            <div class="text-muted small">{{ $event_trooper->trooper->legal_name }}</div>
            @if(($can_moderate ?? false) && $event_trooper->trooper->is_untrusted)
                <span class="badge text-bg-warning">{{ __('events.Needs verification') }}</span>
            @endif
            @if($event_trooper->added_by_trooper_id > 0 && $event_trooper->added_by_trooper_id !== $event_trooper->trooper_id && $event_trooper->added_by_trooper)
                <div class="text-muted small fst-italic">
                    {{ __('events.Added by :name', ['name' => $event_trooper->added_by_trooper->display_name]) }}
                </div>
            @endif
        </div>
        <div class="roster-row__costume">
            @include('pages.events.inc.trooper-costume-controls', ['event_trooper' => $event_trooper, 'read_only' => true, 'labelled' => false])
        </div>
        <div class="roster-row__status">
            @include('pages.events.inc.trooper-status-controls', ['event_trooper' => $event_trooper, 'part' => 'status'])
        </div>
        <div class="roster-row__edit">
            @if($can_edit)
                <button type="button"
                        class="btn btn-sm btn-outline-secondary btn-secondary-action"
                        aria-controls="roster-row-editor-{{ $event_trooper->id }}"
                        x-bind:aria-expanded="editing"
                        x-on:click="editing = !editing">
                    <span x-show="!editing">{{ __('events.Edit') }}</span>
                    <span x-show="editing"
                          x-cloak>{{ __('events.Done') }}</span>
                </button>
            @endif
        </div>
    </div>
    @if($can_edit)
        {{-- no id on the element x-show hides: htmx "settles" a swapped-in element that keeps its
             id by copying the old one's attributes over and back, which wipes Alpine's display:none --}}
        <div class="roster-row__editor"
             x-show="editing"
             @unless($opens_itself) x-cloak @endunless>
            <div id="roster-row-editor-{{ $event_trooper->id }}">
            <div class="row g-3">
                <div class="col-12 col-md-7">
                    @if($event_trooper->canUpdateCostume($viewer))
                        @include('pages.events.inc.trooper-costume-controls', ['event_trooper' => $event_trooper, 'read_only' => false, 'labelled' => true])
                    @endif
                </div>
                <div class="col-12 col-md-5 align-self-start text-md-end roster-row__editor-actions">
                    @if($event_trooper->canUpdateCostume($viewer) && ($event->requiresCostume() || $event_shift->usesStations()))
                        {{-- the height of the picker's label, so plans and Cancel line up with the picker beside them --}}
                        <span class="form-label small mb-1 d-none d-md-block invisible"
                              aria-hidden="true">&nbsp;</span>
                    @endif
                    @include('pages.events.inc.trooper-status-controls', ['event_trooper' => $event_trooper, 'part' => 'edit'])
                </div>
            </div>
            </div>
        </div>
    @endif
</div>

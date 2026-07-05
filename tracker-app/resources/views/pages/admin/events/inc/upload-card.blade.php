@php
    $is_administrative = (bool) $event_upload->is_administrative;
    $refresh_target = $is_administrative ? '#event-uploads' : '#mission-review-photos';
    $indicator = $is_administrative ? '#transmission-bar-event' : '#transmission-bar-mission-review';
    $move_label = $is_administrative ? 'Send to Mission Review' : 'Move to Admin Uploads';
    $move_icon = $is_administrative ? 'fa-share' : 'fa-folder';
    $move_confirmation = $is_administrative
        ? 'Move this photo to Mission Review? It may become visible in member event photos and the service-record photo gallery.'
        : 'Move this photo to Admin Uploads? It will be removed from Mission Review and member/gallery photo views.';
@endphp

<div class="col" id="upload-{{ $event_upload->id }}">
    <div class="card h-100">
        <img src="{{ $event_upload->small_url }}"
             class="card-img-top rounded-top"
             alt="Upload {{ $event_upload->id }}" />
        @if(! $is_administrative)
            <div class="card-body p-2">
                <small class="text-muted d-block">{{ $event_upload->trooper->display_name }}</small>
                @if($event_upload->troopers->isNotEmpty())
                    <small class="text-muted d-block">
                        Tagged: {{ $event_upload->troopers->pluck('display_name')->join(', ') }}
                    </small>
                @endif
            </div>
        @endif
        <div class="card-footer p-2">
            <form hx-post="{{ route('admin.events.uploads.toggle-type', compact('event', 'event_upload')) }}"
                  hx-target="{{ $refresh_target }}"
                  hx-select="{{ $refresh_target }}"
                  hx-swap="outerHTML"
                  hx-indicator="{{ $indicator }}"
                  hx-confirm="{{ $move_confirmation }}"
                  class="mb-2">
                @csrf
                <button type="submit"
                        class="btn btn-sm btn-outline-primary w-100">
                    <i class="fa {{ $move_icon }} me-1"></i> {{ $move_label }}
                </button>
            </form>
            <form hx-post="{{ route('admin.events.uploads.delete', compact('event', 'event_upload')) }}"
                  hx-target="{{ $refresh_target }}"
                  hx-select="{{ $refresh_target }}"
                  hx-swap="outerHTML"
                  hx-indicator="{{ $indicator }}"
                  hx-confirm="Permanently delete this photo?">
                @csrf
                <button type="submit"
                        class="btn btn-sm btn-danger w-100">
                    <i class="fa fa-trash me-1"></i> Delete
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Rendered into the Svelte Update Event Type page, so it is plain HTML with its own form. --}}
<div class="card mt-4">
    <div class="card-header">
        <i class="fa fa-fw fa-comments"></i>
        Forum
    </div>
    <div class="card-body">
        <form method="POST"
              action="{{ route('plugins.xenforo.admin.event-types.forum', ['event_type' => $event_type]) }}">
            @csrf
            <p class="small text-muted">
                Optional. Leave empty to use the hosting organization's forums.
            </p>
            <div class="mb-3">
                <label class="form-label"
                       for="xenforo_event_forum_id">Event forum (node ID)</label>
                <input type="number"
                       min="1"
                       class="form-control"
                       id="xenforo_event_forum_id"
                       name="xenforo_event_forum_id"
                       value="{{ $xenforo_forum?->event_forum_id }}" />
                <div class="form-text">Where threads for events of this type are created.</div>
            </div>
            <div class="mb-3">
                <label class="form-label"
                       for="xenforo_archive_forum_id">Archive forum (node ID)</label>
                <input type="number"
                       min="1"
                       class="form-control"
                       id="xenforo_archive_forum_id"
                       name="xenforo_archive_forum_id"
                       value="{{ $xenforo_forum?->archive_forum_id }}" />
                <div class="form-text">Where those threads move when the event closes.</div>
            </div>
            <button type="submit"
                    class="btn btn-primary">Save forums</button>
        </form>
    </div>
</div>

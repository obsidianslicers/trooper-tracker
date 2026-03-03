<div id="event-uploads">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
        @foreach($event->event_uploads as $event_upload)
            @if($event_upload->is_administrative == $is_administrative)
                <div class="col">
                    <div class="card h-100 position-relative {{ $is_administrative ? '' : 'event-image-share-wrapper'}}">
                        <img src="{{ $event_upload->small_url }}"
                             class="card-img-top rounded"
                             alt="Image #{{ $event_upload->id }}" />
                        <div class="event-image-share-buttons">
                            {!! Share::page(route('shares.event', compact('event', 'event_upload')), $event->name)->facebook()->twitter() !!}
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
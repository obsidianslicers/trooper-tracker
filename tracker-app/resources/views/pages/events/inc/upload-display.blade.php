@php $section_id = $is_administrative ? 'admin-event-uploads' : 'event-uploads'; @endphp
<div id="{{ $section_id }}">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
        @foreach($event->event_uploads as $event_upload)
            @if($event_upload->is_administrative == $is_administrative)
                @php
                    $isTaggedForMe = $event_upload->troopers->contains('id', auth()->id());
                @endphp

                <div class="col">
                    <div class="card h-100 position-relative event-image-share-wrapper">
                        <img src="{{ $event_upload->small_url }}"
                             class="card-img-top rounded"
                             alt="Image #{{ $event_upload->id }}" />
                        <div class="event-image-share-buttons">
                            {!! Share::page(route('shares.event-upload', compact('event', 'event_upload')), $event->name)->facebook()->twitter() !!}

                            <form hx-post="{{ route('events.toggle-upload-tag', compact('event_upload')) }}"
                                  hx-select="#{{ $section_id }}"
                                  hx-target="#{{ $section_id }}"
                                  hx-swap="outerHTML"
                                  hx-indicator="#transmission-bar-upload-images"
                                  class="mt-2">
                                @csrf
                                <button type="submit"
                                        class="btn btn-sm {{ $isTaggedForMe ? 'btn-warning' : 'btn-outline-light' }}">
                                    <i class="fa fa-fw {{ $isTaggedForMe ? 'fa-user-times' : 'fa-user-plus' }} me-1"></i>
                                    {{ $isTaggedForMe ? 'Untag me' : 'Tag me' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
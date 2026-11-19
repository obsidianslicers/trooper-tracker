@php $section_id = $is_administrative ? 'admin-event-uploads' : 'event-uploads'; @endphp
<div id="{{ $section_id }}">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
        @foreach($event->event_uploads as $event_upload)
            @if($event_upload->is_administrative == $is_administrative)
                <div class="col">
                    <div class="card h-100 position-relative event-image-share-wrapper">
                        <a href="{{ $event_upload->large_url }}"
                           class="d-block event-upload-photo-trigger"
                           data-bs-toggle="modal"
                           data-bs-target="#eventUploadPhotoModal"
                           data-photo-url="{{ $event_upload->large_url }}">
                            <img src="{{ $event_upload->small_url }}"
                                 class="card-img-top rounded"
                                 alt="{{ __('events.Image #:id', ['id' => $event_upload->id]) }}" />
                        </a>
                        @if($is_administrative && $event_upload->trooper)
                            <div class="card-body p-2">
                                <small class="text-muted">
                                    {{ __('events.Uploaded by :name', ['name' => $event_upload->trooper->display_name]) }}
                                </small>
                            </div>
                        @endif
                        <div class="event-image-share-buttons">
                            {!! Share::page(route('shares.event-upload', compact('event', 'event_upload')), $event->name)->facebook()->twitter() !!}

                            @if(! $is_administrative)
                                @php $isTaggedForMe = $event_upload->troopers->contains('id', auth()->id()); @endphp
                                <form hx-post="{{ route('events.toggle-upload-tag', compact('event_upload')) }}"
                                      hx-select="#event-uploads-panel"
                                      hx-target="#event-uploads-panel"
                                      hx-swap="outerHTML"
                                      hx-indicator="#transmission-bar-upload-images"
                                      class="mt-2">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-sm {{ $isTaggedForMe ? 'btn-warning' : 'btn-outline-light' }}">
                                        <i class="fa fa-fw {{ $isTaggedForMe ? 'fa-user-times' : 'fa-user-plus' }} me-1"></i>
                                        {{ $isTaggedForMe ? __('events.Untag me') : __('events.Tag me') }}
                                    </button>
                                </form>
                            @endif

                            @can('changeType', $event_upload)
                                <form hx-post="{{ route('events.toggle-upload-type', compact('event_upload')) }}"
                                      hx-select="#event-uploads-panel"
                                      hx-target="#event-uploads-panel"
                                      hx-swap="outerHTML"
                                      hx-indicator="#transmission-bar-upload-images"
                                      class="mt-2">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-light">
                                        <i class="fa fa-fw {{ $is_administrative ? 'fa-camera' : 'fa-map' }} me-1"></i>
                                        {{ $is_administrative ? __('events.Move to Mission Review') : __('events.Move to Instructional') }}
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>

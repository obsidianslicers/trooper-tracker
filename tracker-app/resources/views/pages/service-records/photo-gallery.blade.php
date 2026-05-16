@extends('layouts.base')

@section('page-title', 'Photo Gallery')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex align-items-center mb-4">
        <h2 class="h4 mb-0 text-uppercase fw-bold">
            <i class="fa-solid fa-images me-2"></i> Photo Gallery
        </h2>
    </div>

    @if($uploads->isEmpty())
        <p class="text-muted">No event photos have been uploaded yet.</p>
    @else
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
            @foreach($uploads as $upload)
                <div class="col">
                    <div class="card h-100">
                        <div class="ratio ratio-1x1">
                            <a href="{{ $upload->large_url }}"
                               class="d-block w-100 h-100 photo-gallery-trigger"
                               data-bs-toggle="modal"
                               data-bs-target="#photoGalleryModal"
                               data-photo-url="{{ $upload->large_url }}">
                                <img src="{{ $upload->small_url }}"
                                     alt="Event photo"
                                     style="object-fit: cover; width: 100%; height: 100%;" />
                            </a>
                        </div>
                        <div class="card-body p-2">
                            <div class="small fw-semibold text-truncate">
                                <a href="{{ route('events.display', $upload->event) }}"
                                   class="text-decoration-none">
                                    {{ $upload->event->name }}
                                </a>
                            </div>
                            <div class="small text-muted">
                                {{ $upload->event->event_start?->format('M j, Y') }}
                            </div>
                            <div class="small text-muted text-truncate">
                                By {{ $upload->trooper->display_name }}
                            </div>
                            @if($upload->troopers->isNotEmpty())
                                <div class="small text-muted text-truncate mt-1">
                                    <i class="fa fa-tag me-1"></i>{{ $upload->troopers->pluck('display_name')->join(', ') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $uploads->links() }}
        </div>
    @endif

</div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('photoGalleryModal');
            if (!modal) return;

            modal.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger) return;

                var url = trigger.getAttribute('data-photo-url');
                var image = modal.querySelector('#photoGalleryModalImage');

                if (image && url) {
                    image.setAttribute('src', url);
                }
            });
        });
    </script>

    <div class="modal fade"
         id="photoGalleryModal"
         tabindex="-1"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
            <div class="modal-content bg-dark border-0">
                <div class="modal-body p-0 position-relative">
                    <button type="button"
                            class="btn-close btn-close-white position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>

                    <img id="photoGalleryModalImage"
                         src=""
                         alt="Event photo"
                         class="img-fluid w-100" />
                </div>
            </div>
        </div>
    </div>
@endsection

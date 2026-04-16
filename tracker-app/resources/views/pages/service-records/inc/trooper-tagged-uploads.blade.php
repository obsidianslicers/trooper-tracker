<div class="row g-3">
    @foreach ($tagged_uploads as $event_upload)
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card h-100">
                <div class="ratio ratio-1x1">
                    <a href="{{ $event_upload->large_url }}"
                       class="d-block w-100 h-100 service-record-photo-trigger"
                       data-bs-toggle="modal"
                       data-bs-target="#serviceRecordPhotoModal"
                       data-photo-url="{{ $event_upload->large_url }}">
                        <img src="{{ $event_upload->small_url }}"
                             class="img-fluid"
                             alt="Service record photo"
                             style="object-fit: cover; width: 100%; height: 100%;" />
                    </a>
                </div>
            </div>
        </div>
    @endforeach
</div>
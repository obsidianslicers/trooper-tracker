<div class="row g-3">
    @foreach ($tagged_uploads as $event_upload)
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card h-100">
                <div class="ratio ratio-1x1">
                    <img src="{{ $event_upload->small_url }}"
                         class="img-fluid"
                         style="object-fit: cover;" />
                </div>
            </div>
        </div>
    @endforeach
</div>
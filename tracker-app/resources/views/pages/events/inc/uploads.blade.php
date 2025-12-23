<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
    @foreach($event->event_uploads as $event_upload)
        <div class="col">
            <div class="card h-100">
                <img src="{{ $event_upload->filename }}"
                     class="card-img-top"
                     alt="">
            </div>
        </div>
    @endforeach
</div>
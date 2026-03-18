<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
    @forelse ($trooper_costumes as $trooper_costume)
        <div class="col">
            <div class="card h-100">

                <div class="card-header image-container-wrapper p-3">
                    @if ($trooper_costume->image_urls && $trooper_costume->image_urls->isNotEmpty())
                        <div class="d-flex gap-2 overflow-auto pb-2 custom-scrollbar">
                            @foreach ($trooper_costume->image_urls as $image_url)
                                <div class="flex-shrink-0">
                                    <img src="{{ $image_url }}"
                                         alt="{{ $trooper_costume->name }}"
                                         class="rounded border"
                                         style="width: 80px; height: 80px; object-fit: cover;">
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded d-flex align-items-center justify-content-center"
                             style="height: 80px; border: 2px dashed #454545;">
                            <i class="fa fa-fw fa-user text-muted fs-4 pb-2"></i>
                        </div>
                    @endif
                </div>

                <div class="card-body d-flex flex-column">
                    <h5 class="card-title fw-bold mb-1">
                        {{ $trooper_costume->name }}
                    </h5>

                    <p class="card-text small mb-3">
                        {{ $trooper_costume->costume_organizations }}
                    </p>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5 bg-light rounded border">
                @if(!$trooper->is_handler)
                    <i class="bi bi-person-badge fs-1"></i>
                    <p class="mt-2 text-muted">No Attached Costumes ... Yet!</p>
                @endif
            </div>
        </div>
    @endforelse
</div>

<style>
    /* 1. Define a fixed height for the image area */
    /* 80px (image) + 8px (scrollbar/padding) = 88px total height */
    .image-container-wrapper {
        height: 100px;
    }

    /* Clean up the scrollbar for the image row */
    .custom-scrollbar::-webkit-scrollbar {
        height: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        overflow-x: scroll !important;
        background: #dee2e6;
        border-radius: 10px;
    }
</style>
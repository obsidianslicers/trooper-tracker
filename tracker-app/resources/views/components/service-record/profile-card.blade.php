@props(['label'])

<div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center p-3">
            <div class="text-muted text-uppercase small fw-bold mb-1">
                {{ $label }}
            </div>
            <div class="h3 mb-0 text-primary fw-bold">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
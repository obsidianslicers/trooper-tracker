@props(['id' => 'card-' . uniqid(), 'label', 'open' => false, 'danger' => false, 'copylink' => false])

<div class="card mb-3 {{ $danger ? 'border-danger' : '' }}">
    <div class="card-header d-flex justify-content-between align-items-center"
         data-bs-toggle="collapse"
         data-bs-target="#{{ $id }}"
         role="button">
        <span>{{ $label }}</span>
        <div class="d-flex align-items-center gap-2">
            @if($copylink)
                <button type="button"
                        class="btn btn-link p-0 text-muted border-0 bg-transparent faq-copy-link"
                        data-copy-id="{{ $id }}"
                        title="Copy link to this item">
                    <i class="fa fa-fw fa-copy"></i>
                </button>
            @endif
            <i class="fa-solid fa-{{ $open ? 'minus' : 'plus'}} collapse-icon"></i>
        </div>
    </div>
    <div id="{{ $id }}"
         class="collapse {{ $open ? 'show' : '' }}">
        <div class="card-body">
            {{ $slot }}
        </div>
    </div>
</div>
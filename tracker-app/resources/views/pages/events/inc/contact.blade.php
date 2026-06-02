<x-section-title>Commanding Officer</x-section-title>
@if(!empty($event->contact_name))
    <div class="mt-3 ps-3">
        <p class="mb-1">
            <i class="fa fa-fw fa-user me-2"></i>
            {{ $event->contact_name }}
        </p>
        @if(!empty($event->contact_phone))
            <p class="mb-1">
                <a href="tel:{{ $event->contact_phone }}">
                    <i class="fa fa-fw fa-phone me-2"></i>
                    {{ $event->contact_phone }}
                </a>
            </p>
        @endif
        @if(!empty($event->contact_email))
            <p class="mb-1">
                <a href="mailto:{{ $event->contact_email }}">
                    <i class="fa fa-fw fa-envelope me-2"></i>
                    {{ $event->contact_email }}
                </a>
            </p>
        @endif
    </div>
@else
    <div class="mt-3 ps-3">
        <p class="mb-1 text-muted">
            Unavailable
        </p>
    </div>
@endif
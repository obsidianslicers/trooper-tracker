@if($event->contact_name)
    <x-section-title>Contact</x-section-title>
    <div class="mt-3 ps-3">
        <p class="mb-1">
            <i class="fa fa-fw fa-user me-2"></i>
            {{ $event->contact_name }}
        </p>
        <p class="mb-1">
            <a href="tel:{{ $event->contact_phone }}">
                <i class="fa fa-fw fa-phone me-2"></i>
                {{ $event->contact_phone }}
            </a>
        </p>
        <p class="mb-1">
            <a href="mailto:{{ $event->contact_email }}">
                <i class="fa fa-fw fa-envelope me-2"></i>
                {{ $event->contact_email }}
            </a>
        </p>
    </div>
@endif
@if($event->created_by)
    <div class="mt-3">
        <x-section-title>Event Coordinator</x-section-title>
        <div class="mt-3 ps-3">
            <p class="mb-1">
                <a href="{{ route('service-records.trooper', ['trooper' => $event->created_by->id]) }}">
                    <i class="fa fa-fw fa-user-shield me-2"></i>
                    {{ $event->created_by->display_name }}
                </a>
            </p>
        </div>
    </div>
@endif

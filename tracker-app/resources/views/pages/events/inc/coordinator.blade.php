@php($coordinator = $event->eventCoordinator())
@if($coordinator)
    <div class="mt-3">
        <x-section-title>{{ __('events.Event Coordinator') }}</x-section-title>
        <div class="mt-3 ps-3">
            <p class="mb-1">
                <a href="{{ route('service-records.trooper', ['trooper' => $coordinator->id]) }}">
                    <i class="fa fa-fw fa-user-shield me-2"></i>
                    {{ $coordinator->display_name }}
                </a>
            </p>
        </div>
    </div>
@endif

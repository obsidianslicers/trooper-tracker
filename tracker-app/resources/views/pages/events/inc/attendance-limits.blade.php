<x-section-title>Club Quotas</x-section-title>
<ul class="list-group list-group-flush">
    <li class="list-group-item fw-bold">
        Organization
        <span class="float-end">
            <span class="d-none d-md-inline">
                Troopers / Handlers
            </span>
            <span class="d-inline d-md-none">
                Trp / Hnd
            </span>
        </span>
    </li>
    @foreach($event->organizations as $organization)
        <li class="list-group-item bg-transparent border-bottom">
            <x-yes-no class="me-2"
                      :value="$organization->pivot->can_attend ?? false" />
            {{ $organization->name }}
            @if($organization->pivot->can_attend)
                <span class="float-end">
                    <x-number-format :value="$organization->pivot->troopers_allowed" />
                    /
                    <x-number-format :value="$organization->pivot->handlers_allowed" />
                </span>
            @endif
        </li>
    @endforeach
</ul>
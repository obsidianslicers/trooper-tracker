<x-section-title>Requested Characters</x-section-title>
<p>
    {{ $event->requested_character_types }}
</p>
<ul class="list-group list-group-flush">
    @foreach($event->organizations as $organization)
        <li class="list-group-item">
            <x-yes-no class="me-2"
                      :value="true" />
            {{ $organization->name }}
        </li>
    @endforeach
</ul>
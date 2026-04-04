<x-transmission-bar :id="'trooper-costumes'" />

<div id="trooper-costumes-form-container">

    <div id="trooper-costumes-table">

        <ul class="list-group list-group-flush">
            <li class="list-group-item fw-bold">
                Attached Costumes
            </li>
            @forelse ($trooper_costumes as $trooper_costume)
                <li class="list-group-item bg-transparent border-bottom">
                    <span class="float-end">
                        <x-button-delete hx-delete="{{ route('account.costumes-htmx', ['costume_id' => $trooper_costume->id]) }}"
                                         hx-select="#trooper-costumes-table"
                                         hx-target="#trooper-costumes-table"
                                         hx-swap="outerHTML"
                                         hx-indicator="#transmission-bar-trooper-costumes" />
                    </span>
                    {{ $trooper_costume->name }}
                    <br />
                    <span class="text-muted small">
                        {{ $trooper_costume->costume_organizations }}
                    </span>
                </li>
            @empty
                <li class="list-group-item">
                    <p class="mb-0">
                        No Attached Costumes ... Yet!
                    </p>
                    <p class="text-muted mb-0">
                        To attach a costume to your armory, find your costume, then
                        select your organization/club, and click "Add to Armory".
                    </p>
                </li>
            @endforelse
        </ul>

    </div>

</div>
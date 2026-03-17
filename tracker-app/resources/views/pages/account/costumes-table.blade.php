<x-transmission-bar :id="'trooper-costumes'" />

<div id="trooper-costumes-form-container">

    <x-table id="trooper-costumes-table">
        <thead>
            <tr>
                <th>Attached Costume</th>
                <th>Organizations</th>
                <th>Remove</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($trooper_costumes as $trooper_costume)
                <tr>
                    <td class="text-nowrap">
                        {{ $trooper_costume->name }}
                    </td>
                    <td>
                        {{ $trooper_costume->costume_organizations }}
                    </td>
                    <td class="text-end">
                        <x-button-delete hx-delete="{{ route('account.costumes-htmx', ['costume_id' => $trooper_costume->id]) }}"
                                         hx-select="#trooper-costumes-table"
                                         hx-target="#trooper-costumes-table"
                                         hx-swap="outerHTML"
                                         hx-indicator="#transmission-bar-trooper-costumes" />
                    </td>
                </tr>
            @empty
                <x-table-empty :colspan="3">
                    No Attached Costumes ... Yet!
                    <p class="text-muted">
                        To attach a costume to your armory, find your costume, then
                        select your organization/club, and click "Add to Armory".
                    </p>
                </x-table-empty>
            @endforelse
        </tbody>
    </x-table>

</div>
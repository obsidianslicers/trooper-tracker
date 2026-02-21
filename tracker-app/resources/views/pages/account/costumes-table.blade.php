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
                        To add a favorite costume, select your organization/club, then
                        simply select a costume to add to your profile.
                    </p>
                </x-table-empty>
            @endforelse
        </tbody>
    </x-table>

</div>
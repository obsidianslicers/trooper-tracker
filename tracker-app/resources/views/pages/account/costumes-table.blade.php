<x-transmission-bar :id="'trooper-costumes'" />

<div id="trooper-costumes-form-container">

    <x-table id="trooper-costumes-table">
        <thead>
            <tr>
                <th>
                    Costume
                </th>
                <th class="text-end">
                    Remove
                </th>
            </tr>
        </thead>
        @forelse($trooper_costumes as $trooper_costume)
        <tr>
            <td>
                {{ $trooper_costume->organization_costume->full_name }}
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
        <tr>
            <td colspan="2">
                No Favorite Costumes ... Yet!
                <p class="text-muted">
                    To add a favorite costume, select your organization/club, then
                    simply select a costume to add to your profile.
                </p>
            </td>
        </tr>
        @endforelse
    </x-table>
</div>
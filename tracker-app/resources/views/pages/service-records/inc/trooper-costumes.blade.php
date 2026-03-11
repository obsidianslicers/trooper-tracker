<x-table>
    <thead>
        <tr>
            <th>Attached Costume</th>
            <th>Organizations</th>
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
            </tr>
        @empty
            <x-table-empty :colspan="2">
                @if(!$trooper->is_handler)
                    No Attached Costumes ... Yet!
                @endif
            </x-table-empty>
        @endforelse
    </tbody>
</x-table>
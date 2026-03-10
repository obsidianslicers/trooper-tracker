<x-table>
    <thead>
        <tr>
            <th></th>
            <th>Organization</th>
            <th>Identifier</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($trooper_organizations as $trooper_organization)
            <tr>
                <td>
                    <x-logo :storage_path="$trooper_organization->image_path_sm ?? ''"
                            :default_path="'img/icons/organization-32x32.png'"
                            :width="32"
                            :height="32" />
                </td>
                <td class="text-nowrap">
                    {{ $trooper_organization->name }}
                </td>
                <td>
                    {{ $trooper_organization->identifier_display }}
                </td>
                <td>
                    {{ $trooper_organization->pivot->identifier }}
                </td>
            </tr>
        @empty
            <x-table-empty :colspan="3">
                No Organization Memberships ... Yet!
            </x-table-empty>
        @endforelse
    </tbody>
</x-table>
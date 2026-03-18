<x-table>
    <thead>
        <tr>
            <th class="text-center">Image</th>
            <th>Attached Costume</th>
            <th>Organizations</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($trooper_costumes as $trooper_costume)
            <tr>
                <td class="text-center align-middle">
                    @if ($trooper_costume->image_urls && $trooper_costume->image_urls->isNotEmpty())
                        <div class="d-inline-flex gap-1 flex-wrap justify-content-center">
                            @foreach ($trooper_costume->image_urls as $imageUrl)
                                <div class="ratio ratio-1x1" style="width: 56px;">
                                    <img src="{{ $imageUrl }}"
                                         alt="{{ $trooper_costume->name }} costume image"
                                         class="img-fluid rounded border"
                                         style="object-fit: cover;" />
                                </div>
                            @endforeach
                        </div>
                    @endif
                </td>
                <td class="text-nowrap">
                    {{ $trooper_costume->name }}
                </td>
                <td>
                    {{ $trooper_costume->costume_organizations }}
                </td>
            </tr>
        @empty
            <x-table-empty :colspan="3">
                @if(!$trooper->is_handler)
                    No Attached Costumes ... Yet!
                @endif
            </x-table-empty>
        @endforelse
    </tbody>
</x-table>
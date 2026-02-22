@props(['colspan'])
<tr>
    <td colspan="{{ $colspan }}"
        class="text-center py-4">
        {{  $slot }}
    </td>
</tr>
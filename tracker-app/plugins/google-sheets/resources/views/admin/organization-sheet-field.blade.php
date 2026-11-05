<x-input-container>
    <x-label>
        Google Sync Sheet ID:
    </x-label>
    <x-input-text :property="'google_sheet_id'"
                  :value="$google_sheet_id" />
    <p class="form-help">
        <i class="text-muted">Optional Google Sheet ID the club sync reads its roster from.</i>
    </p>
</x-input-container>

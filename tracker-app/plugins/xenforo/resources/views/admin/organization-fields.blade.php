@if(App\Facades\TroopTrackerFacade::isXenforoIntegrationConfigured())
    <x-input-container>
        <x-label>
            Related Forum (Node ID):
        </x-label>
        <x-input-text :property="'xenforo_event_forum_id'"
                      :value="$xenforo_forum?->event_forum_id" />
        <p class="form-help">
            <i class="text-muted">Optional XenForo forum node id where event threads should be created.</i>
        </p>
    </x-input-container>

    <x-input-container>
        <x-label>
            Related Forum Archive (Node ID):
        </x-label>
        <x-input-text :property="'xenforo_archive_forum_id'"
                      :value="$xenforo_forum?->archive_forum_id" />
        <p class="form-help">
            <i class="text-muted">Optional XenForo archive forum node id for moved/archived threads.</i>
        </p>
    </x-input-container>

    <x-input-container>
        <x-label>
            XenForo Group (Active):
        </x-label>
        <x-input-text :property="'xenforo_active_group_id'"
                      :value="$xenforo_forum?->active_group_id" />
        <p class="form-help">
            <i class="text-muted">Optional XenForo secondary group ID applied when members of this organization (or its units) are active.</i>
        </p>
    </x-input-container>

    <x-input-container>
        <x-label>
            XenForo Group (Reserve):
        </x-label>
        <x-input-text :property="'xenforo_reserve_group_id'"
                      :value="$xenforo_forum?->reserve_group_id" />
        <p class="form-help">
            <i class="text-muted">Optional XenForo secondary group ID applied when members are in reserve status.</i>
        </p>
    </x-input-container>

    <x-input-container>
        <x-label>
            XenForo Group (Retired):
        </x-label>
        <x-input-text :property="'xenforo_retired_group_id'"
                      :value="$xenforo_forum?->retired_group_id" />
        <p class="form-help">
            <i class="text-muted">Optional XenForo secondary group ID applied when members are retired from this organization.</i>
        </p>
    </x-input-container>
@endif

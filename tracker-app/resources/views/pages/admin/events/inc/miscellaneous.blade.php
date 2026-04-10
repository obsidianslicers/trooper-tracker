<x-accordion-card :label="'Miscellaneous (Mission Brief)'">
    <x-input-container>
        <x-label>Comments:</x-label>
        <x-input-text :property="'comments'"
                      :multiline="true"
                      :value="$event->comments"
                      x-model="form.comments"
                      class="markdown-editor" />
    </x-input-container>

    <x-input-container>
        <x-label>Referred By:</x-label>
        <x-input-text :property="'referred_by'"
                      :value="$event->referred_by"
                      x-model="form.referred_by" />
    </x-input-container>

    @if(App\Facades\TroopTrackerFacade::isXenforoIntegrationConfigured())
        <x-input-container>
            <x-label>Create XenForo Thread for this Event:</x-label>
            <x-input-yesno :property="'create_forum_thread'"
                           :value="$event->create_forum_thread ?? true"
                           x-model="form.create_forum_thread" />
            <x-input-help>
                Set to "No" to skip creating a forum thread when this event is posted.
            </x-input-help>
        </x-input-container>

        <x-input-container>
            <x-label>Forum Thread ID:</x-label>
            <x-input-text :property="'thread_id'"
                          :value="$event->thread_id"
                          x-model="form.thread_id" />
        </x-input-container>

        <x-input-container>
            <x-label>Forum Post ID:</x-label>
            <x-input-text :property="'post_id'"
                          :value="$event->post_id"
                          x-model="form.post_id" />
        </x-input-container>
    @endif
</x-accordion-card>
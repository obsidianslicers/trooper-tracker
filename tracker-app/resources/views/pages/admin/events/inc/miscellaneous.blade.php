<x-accordion-card :label="'Miscellaneous (Mission Brief)'">
    <x-input-container>
        <x-label>Comments:</x-label>
        <x-input-text :property="'comments'"
                      :multiline="true"
                      :value="$event->comments"
                      x-model.fill="form.comments"
                      class="markdown-editor" />
        <x-smilies-picker :smilies="$smilies ?? []"
                          targetId="comments"
                          targetType="easymde"
                          :toolbar="true" />
    </x-input-container>

    <x-input-container>
        <x-label>Require Mission Brief Acknowledgement Before Signup:</x-label>
        <x-input-yesno :property="'require_mission_brief_ack'"
                       :value="$event->require_mission_brief_ack ?? false"
                       x-model.fill="form.require_mission_brief_ack" />
        <x-input-help>
            When set to "Yes", troopers must explicitly acknowledge that they have read the
            mission brief on the event page before any sign-up buttons are shown.
        </x-input-help>
    </x-input-container>

    <x-input-container>
        <x-label>Referred By:</x-label>
        <x-input-text :property="'referred_by'"
                      :value="$event->referred_by"
                      x-model.fill="form.referred_by" />
    </x-input-container>

    @pluginSlot('admin.events.form.fields')
</x-accordion-card>
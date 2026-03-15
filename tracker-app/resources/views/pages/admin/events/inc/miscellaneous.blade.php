<x-accordion-card :label="'Miscellaneous'">
    <x-input-container>
        <x-label>Comments:</x-label>
        <x-input-text :property="'comments'"
                      :multiline="true"
                      :value="$event->comments"
                      class="markdown-editor" />
    </x-input-container>

    <x-input-container>
        <x-label>Referred By:</x-label>
        <x-input-text :property="'referred_by'"
                      :value="$event->referred_by" />
    </x-input-container>
</x-accordion-card>
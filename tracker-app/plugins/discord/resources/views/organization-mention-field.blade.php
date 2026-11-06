<x-input-container>
    <x-label>
        Discord Mention:
    </x-label>
    <x-input-text :property="'discord_mention'"
                  :value="$discord_mention" />
    <p class="form-help">
        <i class="text-muted">Optional Discord role/user mention (e.g. <code>&lt;@&amp;123456789&gt;</code>).</i>
    </p>
</x-input-container>

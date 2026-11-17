<x-input-container>
    <x-label>
        {{ __('discord::admin.Discord Mention:') }}
    </x-label>
    <x-input-text :property="'discord_mention'"
                  :value="$discord_mention" />
    <p class="form-help">
        <i class="text-muted">{!! __('discord::admin.Optional Discord role/user mention (e.g. :example).', ['example' => '<code>&lt;@&amp;123456789&gt;</code>']) !!}</i>
    </p>
</x-input-container>

@props(['smilies' => [], 'targetId', 'targetType' => 'textarea', 'toolbar' => false])

@if(!empty($smilies))
<div x-data="{ open: false }"
     id="smilies-panel-{{ $targetId }}"
     @smilies-toggle-{{ $targetId }}.window="open = !open"
     class="mt-2">
    @if(!$toolbar)
    <button type="button"
            class="btn btn-sm btn-outline-secondary"
            x-on:click="open = !open">
        <i class="far fa-smile me-1"></i>
        Smilies
        <i class="fas fa-chevron-down ms-1 fa-xs" x-bind:class="open ? 'fa-rotate-180' : ''"></i>
    </button>
    @endif

    <div x-show="open"
         x-cloak
         class="mt-2 p-2 border rounded"
         style="max-height: 180px; overflow-y: auto; line-height: 2.2;">
        @foreach($smilies as $smiley)
            @php $code = $smiley['smilie_text'][0] ?? ''; @endphp
            @if($code !== '' && !empty($smiley['image_url']))
                <img src="{{ $smiley['image_url'] }}"
                     alt="{{ $smiley['title'] }}"
                     title="{{ $smiley['title'] }} — {{ $code }}"
                     class="forum-smiley me-1"
                     style="height:1.6rem; cursor:pointer;"
                     onclick="insertSmileyCode({{ json_encode($code) }}, {{ json_encode($targetId) }}, {{ json_encode($targetType) }})" />
            @endif
        @endforeach
    </div>
</div>

<script>
function insertSmileyCode(code, targetId, targetType) {
    const el = document.getElementById(targetId);
    if (!el) return;

    if (targetType === 'easymde' && el._easyMDE) {
        el._easyMDE.codemirror.replaceSelection(code);
        el._easyMDE.codemirror.focus();
        return;
    }

    const start = el.selectionStart ?? el.value.length;
    const end = el.selectionEnd ?? el.value.length;
    el.value = el.value.substring(0, start) + code + el.value.substring(end);
    el.selectionStart = el.selectionEnd = start + code.length;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.focus();
}
</script>
@endif

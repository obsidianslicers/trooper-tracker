{{-- FlashType messages show as toasts (resources/js/custom/toasts.js), as on the Svelte pages. --}}
@php
    $flash = [];

    foreach (\App\Enums\FlashType::cases() as $type)
    {
        foreach ((array) session($type->value) as $message)
        {
            if (is_string($message) && trim($message) !== '')
            {
                $flash[] = ['type' => $type->value, 'message' => $message];
            }
        }
    }
@endphp

@if ($flash !== [])
    <script type="application/json" id="tt-flash">{!! json_encode($flash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mt-2">
        <strong>
            {{ __('layout.Please check the form below for errors') }}
        </strong>
        <ul class="p-0 m-0"
            style="list-style: none;">
            @foreach($errors->all() as $error)
                <li>{{$error}}</li>
            @endforeach
        </ul>
        <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"></button>
    </div>
@endif

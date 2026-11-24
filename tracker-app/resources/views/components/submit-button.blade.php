@props(['uid' => 'button-' . uniqid()])

<button data-id="{{ $uid }}"
        data-action="disable-on-submit"
        {{$attributes->merge(['type' => 'submit', 'class' => 'btn btn-primary'])}}>
    {{ $slot }}
</button>
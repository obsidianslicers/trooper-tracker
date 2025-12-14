@props(['value', 'optional' => false])

<label {{$attributes->class(['form-label'])}}>
    {{ $value ?? $slot }}
</label>
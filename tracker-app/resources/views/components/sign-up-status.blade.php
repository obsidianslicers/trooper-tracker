{{--
    A sign-up's status: its label in the status colour, as a word with no icon.
    :block="true" puts it on its own line, above a hint or buttons.
--}}
@props(['status', 'block' => false])
<span {{ $attributes->class([$status->color(), 'fw-semibold', 'd-block mb-1' => $block]) }}>{{ $status->label() }}</span>

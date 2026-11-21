{{--
    A sign-up's status: its label in the status colour, with the icon from md up.
    :block="true" puts it on its own line, above a hint or buttons.
--}}
@props(['status', 'block' => false])
<span {{ $attributes->class([$status->color(), 'd-block mb-1' => $block]) }}>
    {{ $status->label() }}
    <span class="d-none d-md-inline">{!! $status->iconTag() !!}</span>
</span>

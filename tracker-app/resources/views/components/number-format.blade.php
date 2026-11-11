@props(['value' => 0, 'decimals' => 0, 'prefix' => null, 'nulldisplay' => '-'])

<span>
    @if(isset($prefix))
        {{ $prefix }}
    @endif
    {{-- decimals="cents": show cents only when the amount has them --}}
    {{ $value == 0 ? $nulldisplay : ($decimals === 'cents' ? format_funds($value) : number_format($value, $decimals)) }}
</span>
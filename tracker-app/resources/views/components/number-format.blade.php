@props(['value' => 0, 'decimals' => 0, 'prefix' => null, 'nulldisplay' => '-'])

<span>
    @if(isset($prefix))
        {{ $prefix }}
    @endif
    {{ $value == 0 ? $nulldisplay : number_format($value, $decimals) }}
</span>
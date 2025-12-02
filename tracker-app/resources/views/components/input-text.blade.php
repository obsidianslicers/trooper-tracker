@props(['property', 'disabled'=>false, 'value'=>'', 'multiline'=>false])
@php
$haserror = $errors->has($property);
$bracketed = to_bracket_name( $property);
@endphp
@if($multiline)
<textarea name="{{ $bracketed }}"
          id="{{ $property }}"
          {{$attributes->class(['form-control', 'is-invalid' => $haserror])}}>{{ old($property, $value) }}</textarea>
@else
<input type="text"
       name="{{ $bracketed }}"
       id="{{ $property }}"
       value="{{ old($property, $value) }}"
       @disabled($disabled)
       {{$attributes->class(['form-control', 'is-invalid' => $haserror])}} />
@endif
<x-input-error :property="$property" />
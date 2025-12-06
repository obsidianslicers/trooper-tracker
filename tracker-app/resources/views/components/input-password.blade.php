@props(['property', 'disabled'=>false])
@php
$haserror = $errors->has($property);
$bracketed = to_bracket_name( $property);
@endphp
<input type="password"
       name="{{ $bracketed }}"
       id="{{ $property }}"
       value=""
       @disabled($disabled)
       {{$attributes->class(['form-control', 'is-invalid'=>$haserror])}} />
<x-input-error :property="$property" />
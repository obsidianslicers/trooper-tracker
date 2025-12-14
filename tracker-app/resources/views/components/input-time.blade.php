@props(['property', 'disabled' => false, 'value' => '', 'multiline' => false, 'rows' => 6])
@php
    $haserror = $errors->has($property);
    $bracketed = to_bracket_name($property);
@endphp
<input type="time"
       name="{{ $bracketed }}"
       id="{{ $property }}"
       value="{{ old($property, $value) }}"
       @disabled($disabled)
       {{$attributes->class(['form-control', 'is-invalid' => $haserror])}} />
<x-input-error :property="$property" />
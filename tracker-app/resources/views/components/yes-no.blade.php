@props(['value', 'blank' => false])
@if($value)
    <i {{$attributes->class(['fa fa-fw fa-check text-success'])}}></i>
@elseif(!$blank)
    <i {{$attributes->class(['fa fa-fw fa-times text-danger'])}}></i>
@endif
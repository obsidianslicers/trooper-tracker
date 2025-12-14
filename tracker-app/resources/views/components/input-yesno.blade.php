@props(['disabled' => false, 'placeholder' => false, 'optional' => false, 'property' => '', 'value' => null])

<x-input-select :property="$property"
                :value="$value ? 1 : 0"
                :disabled="$disabled"
                :placeholder="$placeholder"
                :optional="$optional"
                :options="[true => 'Yes', false => 'No']"
                {{$attributes}} />
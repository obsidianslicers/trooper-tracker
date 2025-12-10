@extends('layouts.base')

@section('page-title', 'Troopers Awarded')

@section('content')

<x-table>
  <thead>
    <tr>
      <th>
        Trooper
      </th>
      <th>
        Awarded
      </th>
    </tr>
  </thead>
  <tbody>
    @foreach($troopers as $trooper)
    <tr>
      <td>
        {{ $trooper->name }}
      </td>
      <td>
        {{ $trooper->pivot->award_date->format('M d Y') }}
      </td>
    </tr>
    @endforeach
  </tbody>
</x-table>

@endsection
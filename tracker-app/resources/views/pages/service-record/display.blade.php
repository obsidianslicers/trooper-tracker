@extends('layouts.base')

@section('page-title', 'Service Record')

@section('content')

    @include('pages.service-record.inc.deployment-profile', compact('deployment_profile'))

@endsection
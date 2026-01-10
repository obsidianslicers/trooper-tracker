@extends('layouts.base')

@section('page-title', 'Inactive Account')

@section('content')

    <x-slim-container class="mt-4">

        <x-message>
            <p>
                PLEASE NOTE: Your account is currently inactive. If you believe this is an error, please
                contact an administrator for assistance.
            </p>
        </x-message>

    </x-slim-container>

@endsection
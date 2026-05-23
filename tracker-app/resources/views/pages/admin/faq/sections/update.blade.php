@extends('layouts.base')

@section('page-title', 'Update FAQ Section')

@section('content')

<x-slim-container>

    <x-card>
        <form method="POST" novalidate="novalidate">
            @csrf

            <x-input-container>
                <x-label>Label:</x-label>
                <x-input-text :property="'label'"
                              :value="$section->label" />
            </x-input-container>

            <x-input-container>
                <x-label>Icon:</x-label>
                <x-input-text :property="'icon'"
                              :value="$section->icon" />
                <x-input-help>Font Awesome class, e.g. <code>fa-user-plus</code></x-input-help>
            </x-input-container>

            <x-submit-container>
                <x-submit-button>Update</x-submit-button>
                <x-link-button-cancel :url="route('admin.faq.sections.list')" />
            </x-submit-container>

        </form>
    </x-card>

    <x-trooper-stamps :model="$section" />

</x-slim-container>

@endsection

@extends('layouts.base')

@section('page-title', 'Update FAQ Item')

@section('content')

<x-slim-container>

    <x-card>
        <form method="POST" novalidate="novalidate">
            @csrf

            <x-input-container>
                <x-label>Section:</x-label>
                <x-input-select :property="'section'"
                                :value="$faq->section?->value"
                                :options="$sections" />
            </x-input-container>

            <x-input-container>
                <x-label>Title:</x-label>
                <x-input-text :property="'title'"
                              :value="$faq->title" />
            </x-input-container>

            <x-input-container>
                <x-label>Description (Markdown):</x-label>
                <x-input-text class="markdown-editor"
                              :multiline="true"
                              :rows="10"
                              :property="'description'"
                              :value="$faq->description" />
                <x-input-help>Leave blank for video-only items. Supports **bold**, *italic*, lists, and links.</x-input-help>
            </x-input-container>

            <x-input-container>
                <x-label>Video URL:</x-label>
                <x-input-text :property="'video_url'"
                              :value="$faq->video_url" />
                <x-input-help>Optional YouTube URL (watch or embed format). Leave blank for Q&amp;A items.</x-input-help>
            </x-input-container>

            <x-input-container>
                <x-label>Sort Order:</x-label>
                <x-input-text :property="'sort_order'"
                              :value="$faq->sort_order" />
                <x-input-help>Lower numbers appear first within a section.</x-input-help>
            </x-input-container>

            <x-submit-container>
                <x-submit-button>Update</x-submit-button>
                <x-link-button-cancel :url="route('admin.faq.list')" />
            </x-submit-container>

        </form>
    </x-card>

    <x-trooper-stamps :model="$faq" />

</x-slim-container>

@endsection

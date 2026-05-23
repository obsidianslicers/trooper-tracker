@extends('layouts.base')

@section('page-title', 'Create FAQ Item')

@section('content')

<x-slim-container>

    <x-card>
        <form method="POST" novalidate="novalidate">
            @csrf

            <x-input-container>
                <x-label>Section:</x-label>
                <x-input-select :property="'section_id'"
                                :value="$faq->section_id"
                                :options="$sections"
                                :placeholder="'— Select a section —'" />
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

            <x-submit-container>
                <x-submit-button>Create</x-submit-button>
                <x-link-button-cancel :url="route('admin.faq.list')" />
            </x-submit-container>

        </form>
    </x-card>

</x-slim-container>

@endsection

@extends('layouts.base')

@section('page-title', 'FAQ & Help')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-9">

            <p class="text-muted text-center mb-4">
                Everything you need to know about using Troop Tracker — from signing up to signing in on event day.
            </p>

            @auth
                @if(Auth::user()->is_administrator)
                    <div class="d-flex gap-2 justify-content-end mb-3">
                        <a href="{{ route('admin.faq.create') }}" class="btn btn-sm btn-outline-warning">
                            <i class="fa fa-fw fa-plus me-1"></i> Add Item
                        </a>
                        <a href="{{ route('admin.faq.list') }}" class="btn btn-sm btn-outline-warning">
                            <i class="fa fa-fw fa-gear me-1"></i> Manage FAQ
                        </a>
                    </div>
                @endif
            @endauth

            {{-- Quick-jump anchor pills --}}
            <div class="d-flex flex-wrap gap-2 justify-content-center mb-5">
                @foreach($sections as $section)
                    @if($items->has($section->value))
                        <a href="#{{ $section->value }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fa fa-fw {{ $section->icon() }} me-1"></i>
                            {{ $section->label() }}
                        </a>
                    @endif
                @endforeach
            </div>

            {{-- FAQ sections --}}
            @foreach($sections as $section)
                @php $sectionItems = $items->get($section->value, collect()); @endphp
                @if($sectionItems->isNotEmpty())

                    @if($section === \App\Enums\FaqSection::VIDEOS)
                        {{-- ── How-To Videos ── --}}
                        <h2 id="{{ $section->value }}" class="h5 text-uppercase fw-bold text-muted mb-3 pt-5 d-flex align-items-center gap-2">
                            <span><i class="fa fa-fw {{ $section->icon() }} me-2"></i> {{ $section->label() }}</span>
                            @auth
                                @if(Auth::user()->is_administrator)
                                    <a href="{{ route('admin.faq.create') }}?section={{ $section->value }}"
                                       class="btn btn-sm btn-outline-warning ms-auto"
                                       title="Add video">
                                        <i class="fa fa-fw fa-plus"></i>
                                    </a>
                                @endif
                            @endauth
                        </h2>

                        @php $hasAnyVideo = $sectionItems->whereNotNull('video_url')->isNotEmpty(); @endphp
                        @if(! $hasAnyVideo)
                            <p class="text-muted mb-4 small">Step-by-step walkthroughs of the most common tasks. Videos coming soon.</p>
                        @endif

                        <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
                            @foreach($sectionItems as $item)
                                <div class="col">
                                    <div class="card h-100 border-secondary">
                                        @if($item->video_url)
                                            <div class="ratio ratio-16x9">
                                                <iframe src="{{ $item->embedUrl() }}"
                                                        title="{{ $item->title }}"
                                                        frameborder="0"
                                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                        allowfullscreen></iframe>
                                            </div>
                                        @else
                                            <div class="ratio ratio-16x9 bg-secondary rounded-top d-flex align-items-center justify-content-center">
                                                <div class="d-flex flex-column align-items-center justify-content-center text-muted">
                                                    <i class="fa fa-fw fa-circle-play fa-3x mb-2 opacity-50"></i>
                                                    <span class="small fw-semibold text-uppercase opacity-75">Coming Soon</span>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="card-footer text-center small fw-semibold d-flex align-items-center justify-content-center gap-2">
                                            <span>{{ $item->title }}</span>
                                            @auth
                                                @if(Auth::user()->is_administrator)
                                                    <a href="{{ route('admin.faq.update', $item) }}"
                                                       class="btn btn-sm btn-outline-warning py-0"
                                                       title="Edit">
                                                        <i class="fa fa-fw fa-edit"></i>
                                                    </a>
                                                @endif
                                            @endauth
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    @else
                        {{-- ── Q&A section ── --}}
                        <h2 id="{{ $section->value }}" class="h5 text-uppercase fw-bold text-muted mb-3 pt-4 d-flex align-items-center gap-2">
                            <span><i class="fa fa-fw {{ $section->icon() }} me-2"></i> {{ $section->label() }}</span>
                            @auth
                                @if(Auth::user()->is_administrator)
                                    <a href="{{ route('admin.faq.create') }}?section={{ $section->value }}"
                                       class="btn btn-sm btn-outline-warning ms-auto"
                                       title="Add item to this section">
                                        <i class="fa fa-fw fa-plus"></i>
                                    </a>
                                @endif
                            @endauth
                        </h2>

                        @foreach($sectionItems as $item)
                            <x-accordion-card :label="$item->title" :open="$loop->first && $section === \App\Enums\FaqSection::REGISTRATION">
                                @if($item->video_url)
                                    <div class="ratio ratio-16x9 mb-3">
                                        <iframe src="{{ $item->embedUrl() }}"
                                                title="{{ $item->title }}"
                                                frameborder="0"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                allowfullscreen></iframe>
                                    </div>
                                @endif
                                @if($item->description)
                                    <div class="faq-body">
                                        {!! Str::markdown($item->description) !!}
                                    </div>
                                @endif
                                @auth
                                    @if(Auth::user()->is_administrator)
                                        <div class="mt-2 pt-2 border-top border-secondary">
                                            <a href="{{ route('admin.faq.update', $item) }}"
                                               class="btn btn-sm btn-outline-warning">
                                                <i class="fa fa-fw fa-edit me-1"></i> Edit
                                            </a>
                                        </div>
                                    @endif
                                @endauth
                            </x-accordion-card>
                        @endforeach

                    @endif
                @endif
            @endforeach

        </div>
    </div>

@endsection

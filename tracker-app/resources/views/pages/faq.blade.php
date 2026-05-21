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

            <input type="text" id="faq-search" class="form-control mb-4" placeholder="Search questions...">

            {{-- Quick-jump anchor pills --}}
            <div class="d-flex flex-wrap gap-2 justify-content-center mb-5" id="faq-pills">
                @foreach($sections as $section)
                    @if($items->has($section->id))
                        <a href="#section-{{ $section->id }}" class="btn btn-sm btn-outline-secondary faq-pill" data-section="{{ $section->id }}">
                            <i class="fa fa-fw {{ $section->icon }} me-1"></i>
                            {{ $section->label }}
                        </a>
                    @endif
                @endforeach
            </div>

            {{-- FAQ sections --}}
            @foreach($sections as $section)
                @php $section_items = $items->get($section->id, collect()); @endphp
                @if($section_items->isNotEmpty())

                    <div class="faq-section-group" id="section-{{ $section->id }}">

                        <h2 class="h5 text-uppercase fw-bold text-muted mb-3 pt-4 d-flex align-items-center gap-2">
                            <span><i class="fa fa-fw {{ $section->icon }} me-2"></i> {{ $section->label }}</span>
                            @auth
                                @if(Auth::user()->is_administrator)
                                    <a href="{{ route('admin.faq.create') }}?section_id={{ $section->id }}"
                                       class="btn btn-sm btn-outline-warning ms-auto"
                                       title="Add item to this section">
                                        <i class="fa fa-fw fa-plus"></i>
                                    </a>
                                @endif
                            @endauth
                        </h2>

                        @foreach($section_items as $item)
                            <x-accordion-card :label="$item->title" :open="$loop->parent->first && $loop->first">
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

                    </div>

                @endif
            @endforeach

            <div id="faq-no-results" class="text-muted text-center py-5" style="display:none;">
                No questions match your search.
            </div>

        </div>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('faq-search');
    if (!input) return;

    input.addEventListener('input', function () {
        var term = input.value.trim().toLowerCase();
        var groups = document.querySelectorAll('.faq-section-group');
        var any_visible = false;

        groups.forEach(function (group) {
            if (!term) {
                group.style.display = '';
                group.querySelectorAll('.card').forEach(function (card) {
                    card.style.display = '';
                });
                any_visible = true;
                return;
            }

            var visible_count = 0;
            group.querySelectorAll('.card').forEach(function (card) {
                var text = card.textContent.toLowerCase();
                if (text.indexOf(term) !== -1) {
                    card.style.display = '';
                    visible_count++;
                } else {
                    card.style.display = 'none';
                }
            });

            group.style.display = visible_count > 0 ? '' : 'none';
            if (visible_count > 0) any_visible = true;
        });

        var no_results = document.getElementById('faq-no-results');
        var pills = document.getElementById('faq-pills');
        if (no_results) no_results.style.display = (!term || any_visible) ? 'none' : '';
        if (pills) pills.style.display = term ? 'none' : '';
    });
})();
</script>
@endpush

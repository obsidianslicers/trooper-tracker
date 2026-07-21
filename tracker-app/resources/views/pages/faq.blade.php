@extends('layouts.base')

@section('page-title', 'FAQ & Help')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-9">

            <p class="text-muted text-center mb-4">
                Your mission briefing for Troop Tracker — from enlisting to suiting up on deployment day.
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
                            <x-accordion-card :id="'faq-' . $item->id"
                                          :label="$item->title"
                                          :open="$loop->parent->first && $loop->first"
                                          :copylink="true">
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
                                        <div class="mt-2 pt-2 border-top border-secondary d-flex justify-content-end">
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

            @if(config('app.contact_email'))
                <div id="faq-contact" class="text-center mt-5 pt-4 border-top border-secondary">
                    <p class="text-muted mb-2">Still have questions?</p>
                    <a href="mailto:{{ config('app.contact_email') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-fw fa-envelope me-1"></i> Contact
                    </a>
                </div>
            @endif

            <div id="faq-no-results" class="text-muted text-center py-5" style="display:none;">
                No questions match your search.
                @if(config('app.contact_email'))
                    <div class="mt-3">
                        <a href="mailto:{{ config('app.contact_email') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fa fa-fw fa-envelope me-1"></i> Contact
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </div>

@endsection

@push('scripts')
<script>
(function () {

    // ── Search ────────────────────────────────────────────────────────────
    var input = document.getElementById('faq-search');
    if (input) {
        input.addEventListener('input', function () {
            var term = input.value.trim().toLowerCase();
            var groups = document.querySelectorAll('.faq-section-group');
            var any_visible = false;

            groups.forEach(function (group) {
                if (!term) {
                    group.style.display = '';
                    group.querySelectorAll('.card').forEach(function (card) { card.style.display = ''; });
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
            var pills      = document.getElementById('faq-pills');
            var contact    = document.getElementById('faq-contact');
            if (no_results) no_results.style.display = (!term || any_visible) ? 'none' : '';
            if (pills)      pills.style.display = term ? 'none' : '';
            if (contact)    contact.style.display = (!term || !any_visible) ? '' : 'none';
        });
    }

    // ── Copy link ─────────────────────────────────────────────────────────
    document.querySelectorAll('.faq-copy-link').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var url  = location.origin + location.pathname + '#' + btn.dataset.copyId;
            var icon = btn.querySelector('i');
            navigator.clipboard.writeText(url).then(function () {
                icon.className = 'fa fa-fw fa-check text-success';
                setTimeout(function () { icon.className = 'fa fa-fw fa-link'; }, 1500);
            });
        });
    });

    // ── Hash deep-link: open accordion on load ────────────────────────────
    function openHash(hash) {
        if (!hash || !hash.startsWith('#faq-')) return;
        var collapse = document.querySelector(hash);
        if (!collapse) return;
        var scroll = function () {
            collapse.closest('.card').scrollIntoView({ behavior: 'smooth', block: 'start' });
        };
        if (collapse.classList.contains('show')) {
            scroll();
            return;
        }
        collapse.addEventListener('shown.bs.collapse', scroll, { once: true });
        new bootstrap.Collapse(collapse, { toggle: false }).show();
    }

    // window.bootstrap is set by the deferred Vite bundle, so wait for DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { openHash(location.hash); });
    } else {
        openHash(location.hash);
    }

    window.addEventListener('hashchange', function () { openHash(location.hash); });

    // ── Update hash when an FAQ accordion opens ───────────────────────────
    document.querySelectorAll('.faq-section-group .collapse').forEach(function (el) {
        el.addEventListener('show.bs.collapse', function () {
            history.replaceState(null, '', '#' + el.id);
        });
    });

})();
</script>
@endpush

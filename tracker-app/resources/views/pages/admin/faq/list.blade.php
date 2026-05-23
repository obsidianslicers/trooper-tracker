@extends('layouts.base')

@section('page-title', 'FAQ')

@section('content')

    <div class="row mb-3">
        <div class="col-sm-12 col-md-8">
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.faq.list') }}"
                   class="btn btn-sm {{ $section_id === null ? 'btn-secondary' : 'btn-outline-secondary' }}">
                    All
                </a>
                @foreach($sections as $s)
                    <a href="{{ route('admin.faq.list', ['section_id' => $s->id]) }}"
                       class="btn btn-sm {{ $section_id === $s->id ? 'btn-secondary' : 'btn-outline-secondary' }}">
                        <i class="fa fa-fw {{ $s->icon }} me-1"></i>
                        {{ $s->label }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="col-sm-12 col-md-4 text-end mt-2 mt-md-0 d-flex gap-2 justify-content-end">
            <a href="{{ route('admin.faq.sections.list') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-fw fa-folder me-1"></i>
                Sections
            </a>
            <x-link-button-create :url="route('admin.faq.create')">
                FAQ Item
            </x-link-button-create>
        </div>
    </div>

    @if($sortable)
        <p class="text-muted small mb-2">
            <i class="fa fa-fw fa-grip-vertical me-1"></i>
            Drag rows to reorder.
        </p>
    @endif

    <x-table>
        <thead>
            <tr>
                @if($sortable)
                    <th style="width: 30px;"></th>
                @endif
                <th>Section</th>
                <th>Title</th>
                @if(!$sortable)
                    <th style="width: 60px;">Order</th>
                @endif
                <th style="width: 40px;"></th>
            </tr>
        </thead>
        <tbody id="faq-sortable-tbody">
            @foreach($items as $item)
                <tr data-id="{{ $item->id }}" @if($sortable) style="cursor: grab;" @endif>
                    @if($sortable)
                        <td class="text-muted faq-drag-handle" style="cursor: grab;">
                            <i class="fa fa-fw fa-grip-vertical"></i>
                        </td>
                    @endif
                    <td class="text-nowrap">
                        <i class="fa fa-fw {{ $item->section?->icon }} text-muted me-1"></i>
                        <span class="small text-muted">{{ $item->section?->label }}</span>
                        @if($item->video_url)
                            <i class="fa fa-fw fa-circle-play text-info ms-1" title="Video"></i>
                        @endif
                    </td>
                    <td>{{ $item->title }}</td>
                    @if(!$sortable)
                        <td class="text-muted small">{{ $item->sort_order }}</td>
                    @endif
                    <td>
                        <x-action-menu>
                            <x-action-link-update :url="route('admin.faq.update', ['faq' => $item])" />
                            <li>
                                <form method="POST"
                                      action="{{ route('admin.faq.delete', ['faq' => $item]) }}"
                                      onsubmit="return confirm('Delete this FAQ item?')">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fa fa-fw fa-times text-danger me-3"></i>
                                        Delete
                                    </button>
                                </form>
                            </li>
                        </x-action-menu>
                    </td>
                </tr>
            @endforeach
        </tbody>
        @if(!$sortable)
            <tfoot>
                <tr>
                    <td colspan="4">
                        {{ $items->links() }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </x-table>

@endsection

@if($sortable)
@push('scripts')
<script type="module">
(function () {
    var tbody = document.getElementById('faq-sortable-tbody');
    if (!tbody || typeof Sortable === 'undefined') return;

    Sortable.create(tbody, {
        handle: '.faq-drag-handle',
        animation: 150,
        onEnd: function () {
            var ordered_ids = Array.from(tbody.querySelectorAll('tr[data-id]'))
                .map(function (row) { return row.dataset.id; });

            fetch('{{ route('admin.faq.reorder') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ ids: ordered_ids }),
            });
        },
    });
})();
</script>
@endpush
@endif

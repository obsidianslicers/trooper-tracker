@extends('layouts.base')

@section('page-title', 'FAQ Sections')

@section('content')

    <div class="row mb-3">
        <div class="col-sm-12 col-md-8">
            <a href="{{ route('admin.faq.list') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-fw fa-arrow-left me-1"></i>
                FAQ Items
            </a>
        </div>
        <div class="col-sm-12 col-md-4 text-end mt-2 mt-md-0">
            <x-link-button-create :url="route('admin.faq.sections.create')">
                Section
            </x-link-button-create>
        </div>
    </div>

    <p class="text-muted small mb-2">
        <i class="fa fa-fw fa-grip-vertical me-1"></i>
        Drag rows to reorder.
    </p>

    <x-table>
        <thead>
            <tr>
                <th style="width: 30px;"></th>
                <th style="width: 40px;"></th>
                <th>Label</th>
                <th style="width: 80px;">Items</th>
                <th style="width: 40px;"></th>
            </tr>
        </thead>
        <tbody id="faq-sections-sortable-tbody">
            @foreach($sections as $section)
                <tr data-id="{{ $section->id }}" style="cursor: grab;">
                    <td class="text-muted faq-section-drag-handle" style="cursor: grab;">
                        <i class="fa fa-fw fa-grip-vertical"></i>
                    </td>
                    <td class="text-center">
                        <i class="fa fa-fw {{ $section->icon }} text-muted"></i>
                    </td>
                    <td>{{ $section->label }}</td>
                    <td class="text-muted small">{{ $section->faqs_count }}</td>
                    <td>
                        <x-action-menu>
                            <x-action-link-update :url="route('admin.faq.sections.update', ['section' => $section])" />
                            <li>
                                <form method="POST"
                                      action="{{ route('admin.faq.sections.delete', ['section' => $section]) }}"
                                      onsubmit="return confirm('Delete section &quot;{{ $section->label }}&quot;?')">
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
    </x-table>

@endsection

@push('scripts')
<script type="module">
(function () {
    var tbody = document.getElementById('faq-sections-sortable-tbody');
    if (!tbody || typeof Sortable === 'undefined') return;

    Sortable.create(tbody, {
        handle: '.faq-section-drag-handle',
        animation: 150,
        onEnd: function () {
            var ordered_ids = Array.from(tbody.querySelectorAll('tr[data-id]'))
                .map(function (row) { return row.dataset.id; });

            fetch('{{ route('admin.faq.sections.reorder') }}', {
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

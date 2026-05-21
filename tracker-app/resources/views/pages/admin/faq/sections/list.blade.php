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

    <x-table>
        <thead>
            <tr>
                <th style="width: 40px;"></th>
                <th>Label</th>
                <th style="width: 80px;">Items</th>
                <th style="width: 60px;">Order</th>
                <th style="width: 40px;"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($sections as $section)
                <tr>
                    <td class="text-center">
                        <i class="fa fa-fw {{ $section->icon }} text-muted"></i>
                    </td>
                    <td>{{ $section->label }}</td>
                    <td class="text-muted small">{{ $section->faqs_count }}</td>
                    <td class="text-muted small">{{ $section->sort_order }}</td>
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

@extends('layouts.base')

@section('page-title', 'FAQ')

@section('content')

    <div class="row mb-3">
        <div class="col-sm-12 col-md-8">
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.faq.list') }}"
                   class="btn btn-sm {{ $section === null ? 'btn-secondary' : 'btn-outline-secondary' }}">
                    All
                </a>
                @foreach($sections as $s)
                    <a href="{{ route('admin.faq.list', ['section' => $s->value]) }}"
                       class="btn btn-sm {{ $section === $s->value ? 'btn-secondary' : 'btn-outline-secondary' }}">
                        <i class="fa fa-fw {{ $s->icon() }} me-1"></i>
                        {{ $s->label() }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="col-sm-12 col-md-4 text-end mt-2 mt-md-0">
            <x-link-button-create :url="route('admin.faq.create')">
                FAQ Item
            </x-link-button-create>
        </div>
    </div>

    <x-table>
        <thead>
            <tr>
                <th>Section</th>
                <th>Title</th>
                <th style="width: 60px;">Order</th>
                <th style="width: 40px;"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td class="text-nowrap">
                        <i class="fa fa-fw {{ $item->section->icon() }} text-muted me-1"></i>
                        <span class="small text-muted">{{ $item->section->label() }}</span>
                        @if($item->video_url)
                            <i class="fa fa-fw fa-circle-play text-info ms-1" title="Video"></i>
                        @endif
                    </td>
                    <td>{{ $item->title }}</td>
                    <td class="text-muted small">{{ $item->sort_order }}</td>
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
        <tfoot>
            <tr>
                <td colspan="4">
                    {{ $items->links() }}
                </td>
            </tr>
        </tfoot>
    </x-table>

@endsection

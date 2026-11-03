@extends('layouts.base')

@section('page-title', 'Plugins')

@section('content')

<x-table>
    <thead>
        <tr>
            <th>
                Plugin
            </th>
            <th>
                Version
            </th>
            <th>
                Source
            </th>
            <th>
                Status
            </th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse($plugins as $plugin)
            @php($manifest = $plugin['manifest'])
            <tr>
                <td>
                    <strong>{{ $manifest->name }}</strong>
                    <span class="text-muted small">({{ $manifest->slug }})</span>
                    @if($manifest->description !== '')
                        <div class="small text-muted">{{ $manifest->description }}</div>
                    @endif
                    @if($manifest->author !== '')
                        <div class="small text-muted">By {{ $manifest->author }}</div>
                    @endif
                </td>
                <td>
                    {{ $manifest->version }}
                </td>
                <td>
                    {{ $manifest->is_composer_package ? 'Composer' : 'Local' }}
                </td>
                <td>
                    @if($plugin['is_enabled'] && $plugin['is_loaded'])
                        <span class="badge text-bg-success">Enabled</span>
                    @elseif($plugin['is_enabled'])
                        <span class="badge text-bg-danger"
                              title="Enabled, but it failed to load. Check the logs.">Failed to load</span>
                    @else
                        <span class="badge text-bg-secondary">Disabled</span>
                    @endif
                </td>
                <td class="text-end text-nowrap">
                    @if($manifest->hasSettings())
                        <a href="{{ route('admin.plugins.settings', ['slug' => $manifest->slug]) }}"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="fa fa-fw fa-gear"></i>
                            Settings
                        </a>
                    @endif
                    <form method="POST"
                          class="d-inline"
                          action="{{ route($plugin['is_enabled'] ? 'admin.plugins.disable' : 'admin.plugins.enable', ['slug' => $manifest->slug]) }}">
                        @csrf
                        @if($plugin['is_enabled'])
                            <button type="submit"
                                    class="btn btn-sm btn-outline-danger">
                                Disable
                            </button>
                        @else
                            <button type="submit"
                                    class="btn btn-sm btn-outline-success">
                                Enable
                            </button>
                        @endif
                    </form>
                    @if($plugin['is_installed'])
                        <form method="POST"
                              class="d-inline"
                              action="{{ route('admin.plugins.uninstall', ['slug' => $manifest->slug]) }}">
                            @csrf
                            <button type="submit"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('Uninstall {{ addslashes($manifest->name) }}? Its data and settings will be deleted. This cannot be undone.');">
                                Uninstall
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <x-table-empty :colspan="5">
                No plugins found. Add one to the plugins folder or install one with Composer.
            </x-table-empty>
        @endforelse
    </tbody>
</x-table>

@endsection

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
            @php
                $manifest = $plugin['manifest'];
            @endphp
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
                    @if($plugin['has_pending_upgrade'])
                        <div class="small text-warning"
                             title="Run php artisan plugins:upgrade to finish updating it.">
                            Update pending from {{ $plugin['installed_version'] }}
                        </div>
                    @endif
                </td>
                <td>
                    {{ ucfirst($plugin['source']) }}
                </td>
                <td>
                    @if($plugin['is_enabled'] && $plugin['is_loaded'])
                        <span class="badge text-bg-success">Enabled</span>
                    @elseif($plugin['is_enabled'])
                        <span class="badge text-bg-danger"
                              title="Enabled, but it failed to load. Check the logs.">
                            Failed to load
                        </span>
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
                    @php
                        $toggle_route = $plugin['is_enabled']
                            ? 'admin.plugins.disable'
                            : 'admin.plugins.enable';
                    @endphp
                    <form method="POST"
                          class="d-inline"
                          action="{{ route($toggle_route, ['slug' => $manifest->slug]) }}">
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
                              action="{{ route('admin.plugins.uninstall', ['slug' => $manifest->slug]) }}"
                              data-confirm="Uninstall {{ $manifest->name }}? This deletes its data and settings."
                              onsubmit="return confirm(this.dataset.confirm);">
                            @csrf
                            <button type="submit"
                                    class="btn btn-sm btn-danger">
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

<x-card :label="'Install a plugin'">
    @if($uploads['is_enabled'])
        <form method="POST"
              action="{{ route('admin.plugins.install') }}"
              enctype="multipart/form-data">
            @csrf
            <p class="text-muted small">
                Upload a plugin as a .zip file (up to {{ number_format($uploads['max_kilobytes'] / 1024, 1) }} MB)
                with its plugin.json at the top. It is installed disabled.
                A plugin runs code on the server once enabled, so only install plugins you trust.
            </p>
            <div class="input-group">
                <input type="file"
                       name="archive"
                       class="form-control"
                       accept=".zip,application/zip"
                       required>
                <button type="submit"
                        class="btn btn-primary">
                    Install
                </button>
            </div>
            <x-input-error :property="'archive'" />
        </form>
    @else
        <p class="text-muted small mb-0">
            Installing plugins from here is turned off.
            Set TRACKER_PLUGINS_ALLOW_UPLOADS=true on the server to allow it,
            or add plugins with a deploy (plugins folder or Composer).
        </p>
    @endif
</x-card>

@endsection

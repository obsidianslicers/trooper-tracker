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
                    @if($manifest->capabilities !== [])
                        <div class="small text-muted">
                            It {{ $plugin['capability_summary'] }}.
                        </div>
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
                    @if($plugin['is_enabled'] && $plugin['status'] === 'failed')
                        <span class="badge text-bg-warning"
                              title="Enabled, but the last deploy could not finish setting it up.">
                            Needs attention
                        </span>
                    @elseif($plugin['is_enabled'] && $plugin['is_loaded'])
                        <span class="badge text-bg-success">Enabled</span>
                    @elseif($plugin['is_enabled'])
                        <span class="badge text-bg-danger"
                              title="Enabled, but it failed to load. Check the logs.">
                            Failed to load
                        </span>
                    @elseif($plugin['is_enable_pending'])
                        <span class="badge text-bg-info"
                              title="The next deploy installs its packages and turns it on.">
                            Turns on at next deploy
                        </span>
                    @else
                        <span class="badge text-bg-secondary">Disabled</span>
                    @endif
                    @if($plugin['can_install_on_deploy'] && !$plugin['is_enable_pending'])
                        <div class="small text-muted">
                            Its packages ({{ implode('; ', $plugin['unmet_requirements']) }})
                            are installed by the next deploy after you enable it.
                        </div>
                    @elseif($plugin['unmet_requirements'] !== [] && !$plugin['is_enable_pending'])
                        <div class="small text-danger">
                            Needs {{ implode('; ', $plugin['unmet_requirements']) }}.
                        </div>
                    @endif
                    @if($plugin['last_error'] !== null && $plugin['last_error'] !== '')
                        <div class="small text-danger">
                            Last deploy: {{ $plugin['last_error'] }}
                        </div>
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
                        $toggle_route = $plugin['is_enabled'] || $plugin['is_enable_pending']
                            ? 'admin.plugins.disable'
                            : 'admin.plugins.enable';
                    @endphp
                    <form method="POST"
                          class="d-inline"
                          action="{{ route($toggle_route, ['slug' => $manifest->slug]) }}"
                          @if($toggle_route === 'admin.plugins.enable' && $manifest->capabilities !== [])
                              data-confirm="Turn on {{ $manifest->name }}? It {{ $plugin['capability_summary'] }}."
                              onsubmit="return confirm(this.dataset.confirm);"
                          @endif>
                        @csrf
                        @if($plugin['is_enabled'])
                            <button type="submit"
                                    class="btn btn-sm btn-outline-danger">
                                Disable
                            </button>
                        @elseif($plugin['is_enable_pending'])
                            <button type="submit"
                                    class="btn btn-sm btn-outline-secondary">
                                Cancel
                            </button>
                        @else
                            <button type="submit"
                                    class="btn btn-sm btn-outline-success"
                                    @disabled($plugin['unmet_requirements'] !== []
                                        && !$plugin['can_install_on_deploy'])>
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
                No plugins found. Upload one below or add it to the plugins folder.
            </x-table-empty>
        @endforelse
    </tbody>
</x-table>

@if($missing->isNotEmpty())
    <x-card :label="'Missing plugins'">
        <p class="text-muted small">
            These plugins were set up before, but their files are gone from the server.
            Their settings and data are kept. Put the files back
            and the next deploy restores them as they were.
        </p>
        <ul class="list-group">
            @foreach($missing as $record)
                <li class="list-group-item">
                    <strong>{{ $record->slug }}</strong>
                    <span class="text-muted small">
                        {{ $record->version }}
                        @if($record->source !== null)
                            · {{ ucfirst($record->source) }}
                        @endif
                        · was {{ $record->is_enabled ? 'enabled' : 'disabled' }}
                    </span>
                    @if($record->last_error !== null && $record->last_error !== '')
                        <div class="small text-danger">{{ $record->last_error }}</div>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-card>
@endif

<x-card :label="'Install a plugin'">
    @if($uploads['is_enabled'])
        <form method="POST"
              action="{{ route('admin.plugins.install') }}"
              enctype="multipart/form-data">
            @csrf
            <p class="text-muted small">
                Upload a plugin as a .zip file (up to {{ number_format($uploads['max_kilobytes'] / 1024, 1) }} MB)
                with its plugin.json at the top. It is installed disabled. Composer packages it
                needs are installed by the next deploy.
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
            The server sets TRACKER_PLUGINS_ALLOW_UPLOADS=false. Remove that to allow it,
            or copy plugins into storage/app/plugins on the server.
        </p>
    @endif
</x-card>

@endsection

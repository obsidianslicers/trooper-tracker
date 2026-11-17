@extends('layouts.base')

@section('page-title', __('admin.Plugins'))

@section('content')

<x-table>
    <thead>
        <tr>
            <th>
                {{ __('admin.Plugin') }}
            </th>
            <th>
                {{ __('admin.Version') }}
            </th>
            <th>
                {{ __('admin.Source') }}
            </th>
            <th>
                {{ __('admin.Status') }}
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
                        <div class="small text-muted">{{ __('admin.By :author', ['author' => $manifest->author]) }}</div>
                    @endif
                    @if($manifest->capabilities !== [])
                        <div class="small text-muted">
                            {{ __('admin.It :capabilities.', ['capabilities' => $plugin['capability_summary']]) }}
                        </div>
                    @endif
                </td>
                <td>
                    {{ $manifest->version }}
                    @if($plugin['has_pending_upgrade'])
                        <div class="small text-warning"
                             title="{{ __('admin.Run php artisan plugins:reconcile to finish updating it.') }}">
                            {{ __('admin.Update pending from :version', ['version' => $plugin['installed_version']]) }}
                        </div>
                    @endif
                </td>
                <td>
                    {{ ucfirst($plugin['source']) }}
                </td>
                <td>
                    @if($plugin['is_enabled'] && $plugin['status'] === 'failed')
                        <span class="badge text-bg-warning"
                              title="{{ __('admin.Enabled, but the last deploy could not finish setting it up.') }}">
                            {{ __('admin.Needs attention') }}
                        </span>
                    @elseif($plugin['is_enabled'] && $plugin['is_loaded'])
                        <span class="badge text-bg-success">{{ __('admin.Enabled') }}</span>
                    @elseif($plugin['is_enabled'])
                        <span class="badge text-bg-danger"
                              title="{{ __('admin.Enabled, but it failed to load. Check the logs.') }}">
                            {{ __('admin.Failed to load') }}
                        </span>
                    @elseif($plugin['is_enable_pending'])
                        <span class="badge text-bg-info"
                              title="{{ __('admin.The next deploy installs its packages and turns it on.') }}">
                            {{ __('admin.Turns on at next deploy') }}
                        </span>
                    @else
                        <span class="badge text-bg-secondary">{{ __('admin.Disabled') }}</span>
                    @endif
                    @if($plugin['can_install_on_deploy'] && !$plugin['is_enable_pending'])
                        <div class="small text-muted">
                            {{ __('admin.Its packages (:packages) are installed by the next deploy after you enable it.', ['packages' => implode('; ', $plugin['unmet_requirements'])]) }}
                        </div>
                    @elseif($plugin['unmet_requirements'] !== [] && !$plugin['is_enable_pending'])
                        <div class="small text-danger">
                            {{ __('admin.Needs :requirements.', ['requirements' => implode('; ', $plugin['unmet_requirements'])]) }}
                        </div>
                    @endif
                    @if($plugin['last_error'] !== null && $plugin['last_error'] !== '')
                        <div class="small text-danger">
                            {{ __('admin.Last deploy: :error', ['error' => $plugin['last_error']]) }}
                        </div>
                    @endif
                </td>
                <td class="text-end text-nowrap">
                    @if($manifest->hasSettings())
                        <a href="{{ route('admin.plugins.settings', ['slug' => $manifest->slug]) }}"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="fa fa-fw fa-gear"></i>
                            {{ __('admin.Settings') }}
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
                              data-confirm="Turn on {{ $manifest->name }}? {{ __('admin.It :capabilities.', ['capabilities' => $plugin['capability_summary']]) }}"
                              onsubmit="return confirm(this.dataset.confirm);"
                          @endif>
                        @csrf
                        @if($plugin['is_enabled'])
                            <button type="submit"
                                    class="btn btn-sm btn-outline-danger">
                                {{ __('admin.Disable') }}
                            </button>
                        @elseif($plugin['is_enable_pending'])
                            <button type="submit"
                                    class="btn btn-sm btn-outline-secondary">
                                {{ __('admin.Cancel') }}
                            </button>
                        @else
                            <button type="submit"
                                    class="btn btn-sm btn-outline-success"
                                    @disabled($plugin['unmet_requirements'] !== []
                                        && !$plugin['can_install_on_deploy'])>
                                {{ __('admin.Enable') }}
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
                                {{ __('admin.Uninstall') }}
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <x-table-empty :colspan="5">
                {{ __('admin.No plugins found. Upload one below or add it to the plugins folder.') }}
            </x-table-empty>
        @endforelse
    </tbody>
</x-table>

@if($missing->isNotEmpty())
    <x-card :label="__('admin.Missing plugins')">
        <p class="text-muted small">
            {{ __('admin.These plugins were set up before, but their files are gone from the server. Their settings and data are kept. Put the files back and the next deploy restores them as they were.') }}
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
                        · {{ $record->is_enabled ? __('admin.was enabled') : __('admin.was disabled') }}
                    </span>
                    @if($record->last_error !== null && $record->last_error !== '')
                        <div class="small text-danger">{{ $record->last_error }}</div>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-card>
@endif

<x-card :label="__('admin.Install a plugin')">
    @if($uploads['is_enabled'])
        <form method="POST"
              action="{{ route('admin.plugins.install') }}"
              enctype="multipart/form-data">
            @csrf
            <p class="text-muted small">
                {{ __('admin.Upload a plugin as a .zip file (up to :size MB) with its plugin.json at the top. It is installed disabled. Composer packages it needs are installed by the next deploy. A plugin runs code on the server once enabled, so only install plugins you trust.', ['size' => format_number($uploads['max_kilobytes'] / 1024, 1)]) }}
            </p>
            <div class="input-group">
                <input type="file"
                       name="archive"
                       class="form-control"
                       accept=".zip,application/zip"
                       required>
                <button type="submit"
                        class="btn btn-primary">
                    {{ __('admin.Install') }}
                </button>
            </div>
            <x-input-error :property="'archive'" />
        </form>
    @else
        <p class="text-muted small mb-0">
            {{ __('admin.Installing plugins from here is turned off. The server sets TRACKER_PLUGINS_ALLOW_UPLOADS=false. Remove that to allow it, or copy plugins into storage/app/plugins on the server.') }}
        </p>
    @endif
</x-card>

@endsection

@inject ('crumbs', 'App\Services\BreadCrumbService')

{{-- Event coordinators use the event pages under admin/ but not the Command Staff dashboard --}}
@php($trail = collect($crumbs->getCrumbs())->reject(
    fn ($crumb) => $crumb->url === route('admin.display') && Auth::user()?->is_event_coordinator
))

@if ($trail->isNotEmpty())
    <div class="container-fluid">
        <nav class="mt-1">
            <ol class="breadcrumb border border-0">
                @foreach($trail as $crumb)
                    <li class="breadcrumb-item">
                        @if(empty($crumb->url))
                            {{ $crumb->title }}
                        @else
                            <a href="{{ $crumb->url }}">{{ $crumb->title }}</a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>
@endif

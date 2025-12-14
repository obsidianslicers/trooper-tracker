@extends('layouts.base')

@section('page-title', 'Event Sign-Up')

@section('content')
<x-slim-container>

    {{--
    <pre>
    TODO: share to FB by use, add-to calendars, subscribe updates, etc.
    $pageUrl = urlencode('https://your-website.com/your-page'); // URL of the page to share
    $facebookShareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . $pageUrl;
  </pre>
    --}}
    @php($bg = $event->at_risk ? 'bg-danger' : 'bg-primary')
    @php($bg = $event->is_locked ? 'bg-secondary' : $bg)
    <div class="container my-4">
        <div class="card">
            <div class="card-header {{ $bg }} d-flex align-items-center">
                <span class="p-2">
                    <x-logo :storage_path="$event->organization->image_path_sm ?? ''"
                            :default_path="'img/icons/organization-32x32.png'"
                            :width="32"
                            :height="32" />
                </span>
                <span class="p-2">
                    <h4 class=" text-white">
                        {{ $event->name }}
                    </h4>
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-2 border-bottom">
                    <div class="col-8 small text-muted pb-3">
                        <div class="row">
                            <div class="col-3">
                                Hosted By:
                            </div>
                            <div class="col-9">
                                {{ $event->organization->name }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3">
                                Status:
                            </div>
                            <div class="col-9">
                                {{ to_title($event->status->name) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        @can('update', $event)
                            <div class="btn-group mb-3">
                                <a href="{{ route('admin.events.update', compact('event')) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-fw fa-edit mx-2"></i>
                                </a>
                                <a href="{{ route('admin.events.create', ['copy_id' => $event->id]) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-fw fa-copy mx-2"></i>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>

                <div class="row pb-3 mb-3 border-bottom">
                    <div class="col-12">
                        {{ $event->time_display }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12 col-md-6">
                        @include('pages.events.inc.venue', compact('event'))
                    </div>
                    <div class="col-12 col-md-6">
                        @include('pages.events.inc.contact', compact('event'))
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12 col-md-6">
                        @include('pages.events.inc.amenities', compact('event'))
                    </div>
                    <div class="col-12 col-md-6">
                        @include('pages.events.inc.attendance', compact('event'))
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12 col-md-6">
                        @include('pages.events.inc.characters', compact('event'))
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12 col-md-6">
                        @include('pages.events.inc.charity', compact('event'))
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        @include('pages.events.inc.shifts', compact('event'))
                    </div>
                </div>


                @if($event->comments)
                    <div class="mt-3">
                        <x-section-title>Comments</x-section-title>
                        <p>{!! Str::markdown($event->comments) !!}</p>
                    </div>
                @endif

            </div>
        </div>
    </div>

</x-slim-container>

@endsection
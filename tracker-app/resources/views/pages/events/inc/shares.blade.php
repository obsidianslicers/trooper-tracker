<div class="row">
    <div class="col-12 col-lg-6 mb-2 mb-lg-0">
        @php($link = $event->createCalendarLink())
        <div class="btn-group w-100" role="group" aria-label="Add event to calendar">
            <a href="{{ $link->google() }}"
               class="btn btn-outline-secondary flex-fill"
               target="_blank"
               rel="noopener noreferrer"
               title="Add to Google Calendar">
                <i class="fab fa-google"></i>
            </a>
            <a href="{{ $link->yahoo() }}"
               class="btn btn-outline-secondary flex-fill"
               target="_blank"
               rel="noopener noreferrer"
               title="Add to Yahoo Calendar">
                <i class="fab fa-yahoo"></i>
            </a>
            <a href="{{ $link->webOutlook() }}"
               class="btn btn-outline-secondary flex-fill"
               target="_blank"
               rel="noopener noreferrer"
               title="Add to Outlook Calendar">
                <i class="fab fa-microsoft"></i>
            </a>
            <a href="{{ route('events.download-ics', $event) }}"
               class="btn btn-outline-secondary flex-fill"
               title="Add to Calendar (.ics)">
                <i class="fa fa-fw fa-calendar"></i>
            </a>
        </div>
    </div>
    <div class="col-12 col-lg-6 mb-2 mb-lg-0 d-flex d-lg-block align-items-center justify-content-between gap-2">
        <div class="btn-group" style="overflow-x: auto;">
            @if(App\Facades\TroopTrackerFacade::isXenforoIntegrationConfigured() && !empty($event->thread_id) && !empty($event->post_id))
                <a href="{{ config('services.xenforo.base_url') . '/posts/' . $event->post_id . '/' }}"
                   class="btn btn-outline-secondary"
                   target="_blank"
                   rel="noopener noreferrer"
                   title="View Forum Post">
                    <i class="fa fa-fw fa-comments"></i>
                </a>
            @endif

            @include('pages.events.inc.watch-toggle', compact('event', 'is_watching'))
        </div>

        <div class="mt-0 mt-lg-2 mb-0 mb-lg-3 flex-shrink-0">
            {!! Share::page(route('shares.event', compact('event')), $event->name)->facebook()->twitter() !!}
        </div>
    </div>
</div>

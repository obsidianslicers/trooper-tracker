{{--
    The "How sign-up works" tour: signing up has no form or Submit button, so it shows where each
    step happens. Who it starts by itself for is the tracker.signup_tour setting
    (App\Support\SignUpTour); it then stays quiet on that browser once seen. The button above the
    roster (pages/events/inc/shifts.blade.php) replays it. Behaviour: resources/js/events/signupTour.js.
    The example rows below are a picture only: nothing in them works.
--}}
@php
    $tour_auto_start = \App\Support\SignUpTour::startsFor(Auth::user(), $event);
    $tour_brief_pending = ($mission_brief_required ?? false) && !($has_mission_brief_ack ?? false);
    $tour_status_options = $event->signUpStatusOptions(\App\Enums\EventTrooperStatus::GOING);
    $tour_pending = \App\Enums\EventTrooperStatus::PENDING;
    $tour_going = \App\Enums\EventTrooperStatus::GOING;
@endphp
<div x-data="Events.signupTour({ autoStart: @js($tour_auto_start), storageKey: @js('tt.signupTourSeen.' . Auth::id()) })"
     x-on:signup-tour:start.window="start()"
     x-on:keydown.escape.window="active && end()"
     data-signup-tour
     data-autostart="{{ $tour_auto_start ? 'true' : 'false' }}">

    {{-- the example rows, shown only while the tour runs --}}
    <div class="card mb-3 signup-tour-example"
         x-show="active"
         x-cloak
         inert>
        <div class="card-header d-flex justify-content-between align-items-center gap-2">
            <b>{{ __('events.Example shift') }}</b>
            <span class="badge text-bg-secondary">{{ __('events.Example only, not a real sign-up') }}</span>
        </div>
        <div class="card-body">
            <div class="small text-muted mb-2">{{ __('events.Right after you press Sign Up:') }}</div>
            <div class="row mb-3"
                 data-tour-example="pending">
                <div class="col-7 col-md-4 order-1">
                    <a href="#">{{ Auth::user()->display_name }}</a>
                </div>
                <div class="col-12 col-md-5 order-3 order-md-2">
                    <select class="form-select form-select-sm mt-2 mt-md-0"
                            data-tour-example="costume">
                        <option>{{ __('events.-- Select Costume --') }}</option>
                    </select>
                    <select class="form-select form-select-sm mt-2"
                            data-tour-example="backup">
                        <option>{{ __('events.-- Select Backup Costume (optional) --') }}</option>
                    </select>
                </div>
                <div class="col-5 col-md-3 order-2 order-md-3 text-end">
                    <span class="{{ $tour_pending->color() }}">
                        {{ $tour_pending->label() }}
                        <span class="d-none d-md-inline">{!! $tour_pending->iconTag() !!}</span>
                    </span>
                </div>
            </div>

            <div class="small text-muted mb-2">{{ __('events.After you pick a costume:') }}</div>
            <div class="row mb-3"
                 data-tour-example="going">
                <div class="col-7 col-md-4 order-1">
                    <a href="#">{{ Auth::user()->display_name }}</a>
                </div>
                <div class="col-12 col-md-5 order-3 order-md-2">
                    <b>{{ __('events.Your costume') }}</b>
                    <br />
                    <i class="small text-muted"
                       data-tour-example="backup-shown">
                        <i class="fa fa-fw fa-box-archive"></i>
                        {{ __('events.Your backup costume') }}
                    </i>
                </div>
                <div class="col-5 col-md-3 order-2 order-md-3 text-end">
                    <select class="form-select form-select-sm"
                            data-tour-example="status">
                        @foreach($tour_status_options as $value => $label)
                            <option @selected($value === $tour_going->value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mb-3"
                 data-tour-example="extras">
                <span class="btn btn-sm btn-outline-info">
                    <i class="fa fa-fw fa-plus-circle me-2"></i>
                    {{ __('events.Add a Friend') }}
                </span>
                <span class="btn btn-sm btn-outline-info">
                    <i class="fa fa-fw fa-plus-circle me-2"></i>
                    {{ __('events.Add a Guest') }}
                </span>
            </div>

            <div class="small text-muted mb-2">{{ __('events.After the troop:') }}</div>
            <div class="row"
                 data-tour-example="after">
                <div class="col-12 col-md-4">
                    <a href="#">{{ Auth::user()->display_name }}</a>
                </div>
                <div class="col-12 col-md-8 mt-2 mt-md-0 text-md-end">
                    <div class="input-group input-group-sm d-inline-flex w-auto">
                        <span class="btn btn-outline-success">{{ __('events.Made It') }}</span>
                        <span class="btn btn-outline-danger">{{ __('events.Missed It') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- the steps, in order; a step whose target isn't on the page is left out --}}
    <template data-tour-step
              data-title="{{ __('events.How signing up works') }}">
        {{ __('events.Signing up here has no form to fill in and no Submit button. Everything saves the moment you pick it.') }}
        <br /><br />
        {{ __('events.This takes about a minute, and nothing in it signs you up.') }}
    </template>
    @if($tour_brief_pending)
        <template data-tour-step
                  data-target=".mission-brief-container"
                  data-title="{{ __('events.Read the mission brief first') }}">
            {{ __('events.This event asks you to read the mission brief and confirm you\'ve read it. The Sign Up buttons appear after you do.') }}
        </template>
    @endif
    <template data-tour-step
              data-target=".shift-signup-primary .btn"
              data-title="{{ __('events.Sign up for each shift') }}">
        {!! __('events.Every shift has its own <b>Sign Up</b> button. Pressing it adds you to that shift straight away. Working more than one shift? Sign up for each one.') !!}
    </template>
    {{-- when the shifts are collapsed, or none can be signed up for right now --}}
    <template data-tour-step
              data-target="[id^='shift-container-']"
              data-unless=".shift-signup-primary .btn"
              data-title="{{ __('events.Sign up for each shift') }}">
        {!! __('events.Every shift has its own <b>Sign Up</b> button. Open a shift with its <b>+</b> to see it. Pressing it adds you to that shift straight away. Working more than one shift? Sign up for each one.') !!}
    </template>
    <template data-tour-step
              data-target="[data-tour-example='pending']"
              data-title="{{ __('events.Then pick your costume') }}">
        {!! __('events.You\'re added as <b>Needs Costume</b>. To confirm your spot, pick your costume on your own row of the roster. It saves as soon as you choose. Not wearing one? Pick <i>Attending without a costume</i>.') !!}
    </template>
    <template data-tour-step
              data-target="[data-tour-example='backup']"
              data-title="{{ __('events.A backup costume, if you like') }}">
        {!! __('events.Optional. Once it\'s picked, it shows under your costume with a :icon box icon.', ['icon' => '<i class="fa fa-fw fa-box-archive"></i>']) !!}
    </template>
    <template data-tour-step
              data-target="[data-tour-example='status']"
              data-title="{{ __('events.Change your plans here') }}">
        {{ __('events.After your costume is picked, this menu on your row sets whether you\'re going. This is also where you cancel.') }}
    </template>
    <template data-tour-step
              data-target="[data-tour-example='extras']"
              data-title="{{ __('events.Bringing someone?') }}">
        {{ __('events.Once you\'re going, and if the event allows it, these buttons add a friend who has a tracker account, or a guest who doesn\'t.') }}
    </template>
    <template data-tour-step
              data-target="[data-tour-example='after']"
              data-title="{{ __('events.After the troop') }}">
        {!! __('events.Come back and press <b>Made It</b> or <b>Missed It</b> on your row.') !!}
    </template>
    <template data-tour-step
              data-target="[data-tour-replay]"
              data-title="{{ __('events.That\'s it') }}">
        {{ __('events.You can watch this again any time with this button.') }}
    </template>

    {{-- the dimmed page with a hole around the step's target, and the step's card. The hole is
         placed in page coordinates under <body>, so it scrolls with the page instead of chasing it. --}}
    <template x-teleport="body">
        <div class="signup-tour-spotlight"
             x-show="active"
             x-cloak
             x-bind:class="spot ? '' : 'signup-tour-spotlight--none'"
             x-bind:style="spotStyle"></div>
    </template>
    <div class="card shadow-lg signup-tour-card"
         x-show="active"
         x-cloak
         role="dialog"
         aria-modal="false"
         aria-labelledby="signup-tour-title">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <h6 id="signup-tour-title"
                    class="mb-0 fw-bold"
                    x-text="step?.title"></h6>
                <button type="button"
                        class="btn-close"
                        aria-label="{{ __('events.Close the tour') }}"
                        x-on:click="end()"></button>
            </div>
            <div class="mb-3"
                 x-html="step?.body"></div>
            <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="small text-muted"
                      x-text="(index + 1) + ' of ' + steps.length"></span>
                <div class="d-flex gap-2">
                    <button type="button"
                            class="btn btn-sm btn-link text-muted"
                            x-show="!isLast"
                            x-on:click="end()">{{ __('events.Skip tour') }}</button>
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary"
                            x-show="index > 0"
                            x-on:click="back()">{{ __('events.Back') }}</button>
                    <button type="button"
                            class="btn btn-sm btn-primary"
                            x-ref="next"
                            x-on:click="next()"
                            x-text="isLast ? 'Done' : 'Next'"></button>
                </div>
            </div>
        </div>
    </div>
</div>


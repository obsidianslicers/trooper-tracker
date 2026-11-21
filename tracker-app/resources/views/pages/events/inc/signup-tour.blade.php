{{--
    The "How sign-up works" tour: shows where each step of signing up happens. Who it starts by
    itself for is the tracker.signup_tour setting (App\Support\SignUpTour); it then stays quiet on
    that browser once seen. The button above the roster (pages/events/inc/shifts.blade.php) replays
    it. Behaviour: resources/js/events/signupTour.js.
    The example rows below are a picture only: nothing in them works.
--}}
@php
    $tour_auto_start = \App\Support\SignUpTour::startsFor(Auth::user(), $event);
    $tour_brief_pending = ($mission_brief_required ?? false) && !($has_mission_brief_ack ?? false);
    $tour_status_options = $event->signUpStatusOptions(\App\Enums\EventTrooperStatus::GOING);
    $tour_going = \App\Enums\EventTrooperStatus::GOING;
    //  no costume to pick on an event type troopers don't wear one to: those steps drop out
    $tour_costumes = $event->requiresCostume();
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
            <div class="small text-muted mb-2">{{ __('events.After you press Sign Up:') }}</div>
            <div class="row mb-3"
                 data-tour-example="going">
                <div class="col-7 col-md-4 order-1">
                    <a href="#">{{ Auth::user()->display_name }}</a>
                </div>
                <div class="col-12 col-md-5 order-3 order-md-2">
                    @if($tour_costumes)
                    <select class="form-select form-select-sm mt-2 mt-md-0"
                            data-tour-example="costume">
                        <option>{{ __('events.Your costume') }}</option>
                    </select>
                    <select class="form-select form-select-sm mt-2"
                            data-tour-example="backup">
                        <option>{{ __('events.-- Select Backup Costume (optional) --') }}</option>
                    </select>
                    @endif
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
        {{ $tour_costumes ? __('events.Signing up is one step: pick your costume and press Sign Up. After that, any change saves the moment you pick it.') : __('events.Signing up is one step: press Sign Up. After that, any change saves the moment you pick it.') }}
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
              data-target=".shift-signup-primary--form"
              data-title="{{ __('events.Sign up for each shift') }}">
        {!! $tour_costumes ? __('events.Every shift has its own <b>Sign Up</b>. Pick your costume (the one you wore last time is already picked) and press <b>Sign Up</b>. Not wearing one? Pick <i>Attending without a costume</i>. Working more than one shift? Sign up for each one.') : __('events.Every shift has its own <b>Sign Up</b>. Working more than one shift? Sign up for each one.') !!}
    </template>
    {{-- when the shifts are collapsed, or none can be signed up for right now --}}
    <template data-tour-step
              data-target="[id^='shift-container-']"
              data-unless=".shift-signup-primary--form"
              data-title="{{ __('events.Sign up for each shift') }}">
        {!! $tour_costumes ? __('events.Every shift has its own <b>Sign Up</b>. Open a shift with its <b>+</b> to see it, pick your costume and press <b>Sign Up</b>. Working more than one shift? Sign up for each one.') : __('events.Every shift has its own <b>Sign Up</b>. Open a shift with its <b>+</b> to see it and press <b>Sign Up</b>. Working more than one shift? Sign up for each one.') !!}
    </template>
    @if($tour_costumes)
    <template data-tour-step
              data-target="[data-tour-example='going']"
              data-title="{{ __('events.Then you\'re on the roster') }}">
        {{ __('events.Your own row on the roster shows your costume. Change it there any time; it saves as soon as you choose.') }}
    </template>
    @endif
    <template data-tour-step
              data-target="[data-tour-example='backup']"
              data-title="{{ __('events.A backup costume, if you like') }}">
        {{ __('events.Optional. Pick one in case your main costume can\'t make it.') }}
    </template>
    <template data-tour-step
              data-target="[data-tour-example='status']"
              data-title="{{ __('events.Change your plans here') }}">
        {{ __('events.This menu on your row sets whether you\'re going. This is also where you cancel.') }}
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


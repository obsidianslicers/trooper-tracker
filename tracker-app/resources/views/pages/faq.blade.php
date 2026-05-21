@extends('layouts.base')

@section('page-title', 'FAQ & Help')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-9">

            <p class="text-muted text-center mb-4">
                Everything you need to know about using Troop Tracker — from signing up to signing in on event day.
            </p>

            {{-- Quick-jump anchor pills --}}
            <div class="d-flex flex-wrap gap-2 justify-content-center mb-5">
                <a href="#registration" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-fw fa-user-plus me-1"></i> Registration
                </a>
                <a href="#account-types" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-fw fa-id-card me-1"></i> Account Types
                </a>
                <a href="#organizations" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-fw fa-sitemap me-1"></i> Organizations
                </a>
                <a href="#costumes" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-fw fa-shirt me-1"></i> Costumes
                </a>
                <a href="#events" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-fw fa-calendar me-1"></i> Events
                </a>
                <a href="#signup" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-fw fa-clipboard-check me-1"></i> Signing Up
                </a>
                <a href="#guests" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-fw fa-user-group me-1"></i> Guests
                </a>
                <a href="#friends" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-fw fa-handshake me-1"></i> Friends
                </a>
                <a href="#videos" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-fw fa-circle-play me-1"></i> How-To Videos
                </a>
            </div>

            {{-- ── Registration ── --}}
            <h2 id="registration" class="h5 text-uppercase fw-bold text-muted mb-3 pt-2">
                <i class="fa fa-fw fa-user-plus me-2"></i> Getting Started & Registration
            </h2>

            <x-accordion-card label="How do I create an account?" :open="true">
                <ol class="mb-0">
                    <li class="mb-2">Click <strong>Sign Up</strong> in the top-right corner of the navbar.</li>
                    <li class="mb-2">Choose your sign-up method: <strong>Email</strong>, <strong>Google</strong>, or <strong>XenForo</strong> (if your garrison's installation supports it).</li>
                    <li class="mb-2">Fill in the registration form — see the fields below for details.</li>
                    <li class="mb-2">Select your <strong>Account Type</strong> and <strong>Organization(s)</strong>.</li>
                    <li class="mb-2">Submit the form and wait for <strong>Command Staff approval</strong>.</li>
                    <li>You'll receive an email notification once your account is activated.</li>
                </ol>
            </x-accordion-card>

            <x-accordion-card label="What information do I need to register?">
                <ul class="mb-0">
                    <li class="mb-2"><strong>Legal Name</strong> — used for official records and shared with event coordinators for safety purposes; not displayed publicly.</li>
                    <li class="mb-2"><strong>Display Name</strong> — the name shown publicly on your profile and the event roster.</li>
                    <li class="mb-2"><strong>Email</strong> — used for account communications (pre-filled if you sign up via Google or XenForo).</li>
                    <li class="mb-2"><strong>Phone</strong> — optional; used for event coordinator contact.</li>
                    <li class="mb-2"><strong>Date of Birth &amp; Guardian Email</strong> — required only if you are under 18. Your guardian must already have an active account on the tracker.</li>
                    <li class="mb-0"><strong>Password</strong> — required only if you choose the Email sign-up method.</li>
                </ul>
            </x-accordion-card>

            <x-accordion-card label="How long does account approval take?">
                <p class="mb-0">
                    Approval times vary by garrison and Command Staff availability. Most accounts are reviewed within a few days.
                    You'll receive an email when your account is approved and active. If you haven't heard back after a week,
                    reach out to your garrison's Command Staff directly.
                </p>
            </x-accordion-card>

            <x-accordion-card label="Can I sign up with my existing forum account?">
                <p class="mb-0">
                    Yes — if your garrison has XenForo integration configured, you can use <strong>Sign Up with XenForo</strong>
                    to link your existing forum account. This avoids creating a separate password and keeps your identities connected.
                </p>
            </x-accordion-card>

            {{-- ── Account Types ── --}}
            <h2 id="account-types" class="h5 text-uppercase fw-bold text-muted mb-3 pt-4">
                <i class="fa fa-fw fa-id-card me-2"></i> Account Types
            </h2>

            <x-accordion-card label="What are the different account types?">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card h-100 border-secondary">
                            <div class="card-body">
                                <h6 class="fw-bold text-white">
                                    <i class="fa fa-fw fa-star me-1 text-warning"></i> Member
                                </h6>
                                <p class="small mb-0 text-muted">
                                    Active costumed member of a Star Wars costuming organization. Can sign up for event shifts,
                                    select a costume for each shift, and track costume hours in their service record.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-secondary">
                            <div class="card-body">
                                <h6 class="fw-bold text-white">
                                    <i class="fa fa-fw fa-shield-halved me-1 text-info"></i> Handler
                                </h6>
                                <p class="small mb-0 text-muted">
                                    Handler for a costumed member. No costume is required. Handlers sign up
                                    for shifts and appear on the event roster alongside the trooper they support.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-secondary">
                            <div class="card-body">
                                <h6 class="fw-bold text-white">
                                    <i class="fa fa-fw fa-hourglass-half me-1 text-secondary"></i> Visitor
                                </h6>
                                <p class="small mb-0 text-muted">
                                    Temporary access valid for 6 months. Visitors are assigned to the top-level organization
                                    only — no region or unit selection is required. Useful for visiting members.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-accordion-card>

            <x-accordion-card label="Can I change my account type after registering?">
                <p class="mb-0">
                    Account type changes require Command Staff approval. Contact your garrison's leadership to request
                    a type change (for example, upgrading from Visitor to Member once you change garrisons).
                </p>
            </x-accordion-card>

            <x-accordion-card label="My Visitor access is expiring — how do I renew it?">
                <p class="mb-0">
                    Go to <strong>Account → Setup</strong>. If your visitor period is approaching expiration, you'll see
                    a renewal option there. Renewals are granted at Command Staff discretion.
                </p>
            </x-accordion-card>

            {{-- ── Organizations ── --}}
            <h2 id="organizations" class="h5 text-uppercase fw-bold text-muted mb-3 pt-4">
                <i class="fa fa-fw fa-sitemap me-2"></i> Organizations & Club Memberships
            </h2>

            <x-accordion-card label="How does the organization hierarchy work?">
                <p>Organizations follow a three-level hierarchy:</p>
                <ol class="mb-0">
                    <li class="mb-1"><strong>Organization</strong> — the top-level club (e.g., 501st Legion, Rebel Legion)</li>
                    <li class="mb-1"><strong>Region / Garrison</strong> — a regional chapter within the organization</li>
                    <li><strong>Unit / Squad</strong> — a local sub-group within the region</li>
                </ol>
            </x-accordion-card>

            <x-accordion-card label="How do I select my organization during registration?">
                <ol class="mb-0">
                    <li class="mb-2">On the registration form, check the box next to each organization you belong to.</li>
                    <li class="mb-2">Enter your member ID (e.g., TK number) for that organization if applicable.</li>
                    <li class="mb-2">Select your <strong>Region/Garrison</strong> from the dropdown.</li>
                    <li>Select your <strong>Unit/Squad</strong> once a region is chosen.</li>
                </ol>
            </x-accordion-card>

            <x-accordion-card label="Can I belong to multiple organizations?">
                <p class="mb-0">
                    Yes. Many members hold dual or triple membership (e.g., 501st Legion and Rebel Legion).
                    Check all applicable organizations on the registration form. Each membership is reviewed
                    and approved independently by Command Staff.
                </p>
            </x-accordion-card>

            <x-accordion-card label="How do I update my club memberships after registration?">
                <p class="mb-0">
                    Go to <strong>Account → Club Memberships</strong>. You can add new organizations or update
                    your region/unit there. Changes require Command Staff re-approval before they become active.
                </p>
            </x-accordion-card>

            {{-- ── Costumes ── --}}
            <h2 id="costumes" class="h5 text-uppercase fw-bold text-muted mb-3 pt-4">
                <i class="fa fa-fw fa-shirt me-2"></i> Costumes
            </h2>

            <x-accordion-card label="How do I add a costume to my profile?">
                <ol class="mb-0">
                    <li class="mb-2">Navigate to <strong>Account → Costumes</strong>.</li>
                    <li class="mb-2">Use the search box to find your costume type by name (e.g., "Stormtrooper", "Rebel Fleet Trooper").</li>
                    <li class="mb-2">Click the costume in the results to add it to your profile.</li>
                    <li>Repeat for each costume you own.</li>
                </ol>
            </x-accordion-card>

            <x-accordion-card label="Can I have more than one costume?">
                <p class="mb-0">
                    Absolutely. Add as many costumes as you own. When signing up for an event shift, you'll select
                    which costume you'll be wearing for that specific shift.
                </p>
            </x-accordion-card>

            <x-accordion-card label="How do I remove a costume from my profile?">
                <p class="mb-0">
                    Go to <strong>Account → Costumes</strong>, find the costume in your list, and use the
                    delete action to remove it.
                </p>
            </x-accordion-card>

            <x-accordion-card label="My costume isn't in the list — what do I do?">
                <p class="mb-0">
                    Costume types are managed by Command Staff. If your costume isn't available, contact your
                    garrison administrator to have it added to the system.
                </p>
            </x-accordion-card>

            {{-- ── Events ── --}}
            <h2 id="events" class="h5 text-uppercase fw-bold text-muted mb-3 pt-4">
                <i class="fa fa-fw fa-calendar me-2"></i> Events
            </h2>

            <x-accordion-card label="How do I find upcoming events?">
                <p>Click <strong>Events</strong> in the navbar. You'll find three views:</p>
                <ul class="mb-0">
                    <li class="mb-1"><strong>List</strong> — a filterable table of all upcoming events</li>
                    <li class="mb-1"><strong>Calendar</strong> — a monthly calendar view of events</li>
                    <li><strong>Map</strong> — an interactive map showing event locations</li>
                </ul>
            </x-accordion-card>

            <x-accordion-card label="What information is shown on an event page?">
                <ul class="mb-0">
                    <li class="mb-1">Event name, date, and location</li>
                    <li class="mb-1">Organizing group or beneficiary</li>
                    <li class="mb-1">Available shifts (time slots) with roster capacity</li>
                    <li class="mb-1">Current roster — who is signed up and what costume they're wearing</li>
                    <li>Mission Brief with any important event-specific information</li>
                </ul>
            </x-accordion-card>

            <x-accordion-card label="What is a shift?">
                <p class="mb-0">
                    A shift is a specific time slot within an event. Some events have a single shift covering the whole
                    appearance; others split into multiple shifts (e.g., morning and afternoon). You sign up for the
                    shift(s) you can attend, not the event as a whole.
                </p>
            </x-accordion-card>

            <x-accordion-card label="What is a standby list?">
                <p class="mb-0">
                    Each shift has a maximum roster capacity. If a shift is full when you sign up, you'll be placed
                    on the standby list. If a spot opens up, standby troopers may be moved to the active roster.
                    You'll still appear on the event page so coordinators know you're available.
                </p>
            </x-accordion-card>

            {{-- ── Signing Up ── --}}
            <h2 id="signup" class="h5 text-uppercase fw-bold text-muted mb-3 pt-4">
                <i class="fa fa-fw fa-clipboard-check me-2"></i> Signing Up for Events
            </h2>

            <x-accordion-card label="How do I sign up for an event shift?">
                <ol class="mb-0">
                    <li class="mb-2">Open the event page and locate the shift you want to attend.</li>
                    <li class="mb-2">Click the <strong>Sign Up</strong> button on that shift.</li>
                    <li class="mb-2">Select the <strong>costume</strong> you'll be wearing from your profile.</li>
                    <li class="mb-2">Choose your <strong>role</strong> if applicable (e.g., character role, handler).</li>
                    <li class="mb-2">Read and acknowledge the <strong>Mission Brief</strong>.</li>
                    <li>You'll be added to the active roster (or standby if the shift is full).</li>
                </ol>
            </x-accordion-card>

            <x-accordion-card label="Can I update my signup after the fact?">
                <p class="mb-0">
                    Yes. Return to the event page and use the update option on your shift entry to change your
                    costume selection or role. Updates may be restricted closer to the event date.
                </p>
            </x-accordion-card>

            <x-accordion-card label="How do I withdraw from a shift?">
                <p class="mb-0">
                    Open the event page and find your entry on the shift roster. Use the remove/withdraw option.
                    If you're withdrawing close to the event date, please notify your event coordinator directly
                    so they can adjust plans.
                </p>
            </x-accordion-card>

            <x-accordion-card label="How do I mark my shift as complete?">
                <p class="mb-0">
                    After the event, you may be prompted to confirm your attendance. The system or an event coordinator
                    will mark shifts as complete, which logs the hours to your service record.
                </p>
            </x-accordion-card>

            {{-- ── Guests ── --}}
            <h2 id="guests" class="h5 text-uppercase fw-bold text-muted mb-3 pt-4">
                <i class="fa fa-fw fa-user-group me-2"></i> Guests
            </h2>

            <x-accordion-card label="What is a guest?">
                <p class="mb-0">
                    A guest is a non-costumed attendee who accompanies a trooper to an event — for example, a
                    family member, partner, or media contact. Guests are listed on the event roster under the
                    trooper who added them.
                </p>
            </x-accordion-card>

            <x-accordion-card label="How do I add a guest to an event?">
                <ol class="mb-0">
                    <li class="mb-2">Sign up for the shift first (guests can only be added after you're on the roster).</li>
                    <li class="mb-2">On the event page, find your shift entry and click <strong>Add Guest</strong>.</li>
                    <li class="mb-2">Enter the guest's name.</li>
                    <li>The guest will appear on the roster beneath your entry.</li>
                </ol>
            </x-accordion-card>

            <x-accordion-card label="Can I remove or update a guest?">
                <p class="mb-0">
                    Yes. Return to the event page, find the guest entry under your shift, and use the
                    update or remove action to make changes.
                </p>
            </x-accordion-card>

            {{-- ── Friends ── --}}
            <h2 id="friends" class="h5 text-uppercase fw-bold text-muted mb-3 pt-4">
                <i class="fa fa-fw fa-handshake me-2"></i> Friends
            </h2>

            <x-accordion-card label="What is the friends feature?">
                <p class="mb-0">
                    The friends feature lets you connect with other troopers on the tracker. Friends are highlighted
                    on event rosters, making it easy to coordinate which events you're attending together.
                </p>
            </x-accordion-card>

            <x-accordion-card label="How do I add a friend?">
                <ol class="mb-0">
                    <li class="mb-2">Search for a trooper using the global search bar in the navbar.</li>
                    <li class="mb-2">Open their <strong>Service Record</strong> profile page.</li>
                    <li>Use the friend action on their profile to send a friend request.</li>
                </ol>
            </x-accordion-card>

            <x-accordion-card label="How do I manage my friends list?">
                <p class="mb-0">
                    Your friends list is accessible from your account page. You can view current friends and
                    remove connections that are no longer relevant.
                </p>
            </x-accordion-card>

            {{-- ── How-To Videos ── --}}
            <h2 id="videos" class="h5 text-uppercase fw-bold text-muted mb-3 pt-5">
                <i class="fa fa-fw fa-circle-play me-2"></i> How-To Videos
            </h2>
            <p class="text-muted mb-4 small">Step-by-step walkthroughs of the most common tasks. Videos coming soon.</p>

            <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">

                @php
                    $videos = [
                        ['title' => 'Getting Started & Registration', 'icon' => 'fa-user-plus'],
                        ['title' => 'Setting Up Your Profile',        'icon' => 'fa-pen-to-square'],
                        ['title' => 'Adding Your Costumes',           'icon' => 'fa-shirt'],
                        ['title' => 'Browsing & Joining Events',      'icon' => 'fa-calendar-check'],
                        ['title' => 'Adding Guests to an Event',      'icon' => 'fa-user-group'],
                        ['title' => 'Reading Your Service Record',    'icon' => 'fa-chart-bar'],
                    ];
                @endphp

                @foreach($videos as $video)
                    <div class="col">
                        <div class="card h-100 border-secondary">
                            {{-- 16:9 placeholder --}}
                            <div class="ratio ratio-16x9 bg-secondary rounded-top d-flex align-items-center justify-content-center">
                                <div class="d-flex flex-column align-items-center justify-content-center text-muted">
                                    <i class="fa fa-fw {{ $video['icon'] }} fa-2x mb-2"></i>
                                    <i class="fa fa-fw fa-circle-play fa-3x mb-2 opacity-50"></i>
                                    <span class="small fw-semibold text-uppercase opacity-75">Coming Soon</span>
                                </div>
                            </div>
                            <div class="card-footer text-center small fw-semibold">
                                {{ $video['title'] }}
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>

        </div>
    </div>

@endsection

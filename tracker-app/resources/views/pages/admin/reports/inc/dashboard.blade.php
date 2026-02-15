<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 text-uppercase fw-bold">
            <i class="fa-brands fa-empire me-2"></i>
            Operations Dashboard
        </h2>

        <x-lookback :days="$days" />
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold"
                        style="text-decoration: underline dotted; cursor: help;"
                        data-bs-toggle="tooltip"
                        data-bs-title="Verified troopers currently marked as 'Active'.">
                        <i class="fa-fw fa-solid fa-users-gear me-1"></i>
                        Active Strength
                    </h6>
                    <h2 class="display-6 fw-bold">
                        {{ number_format($dashboard['personnel']['active_count']) }}
                    </h2>
                    <small class="text-muted">Mission Ready</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold"
                        style="text-decoration: underline dotted; cursor: help;"
                        data-bs-toggle="tooltip"
                        data-bs-title="Total number of troopers in the registry.">
                        <i class="fa-fw fa-solid fa-address-card me-1"></i>
                        Trooper Count
                    </h6>
                    <h2 class="display-6 fw-bold">
                        {{ number_format($dashboard['personnel']['trooper_count']) }}
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold"
                        style="text-decoration: underline dotted; cursor: help;"
                        data-bs-toggle="tooltip"
                        data-bs-title="Active support staff and handlers.">
                        <i class="fa-fw fa-solid fa-user-shield me-1"></i>
                        Handler Corps
                    </h6>
                    <h2 class="display-6 fw-bold">
                        {{ number_format($dashboard['personnel']['handler_count']) }}
                    </h2>
                    <small class="text-muted">Support Staff</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold"
                        style="text-decoration: underline dotted; cursor: help;"
                        data-bs-toggle="tooltip"
                        data-bs-title="New members joined (and activated) within this reporting window.">
                        <i class="fa-fw fa-solid fa-user-plus me-1"></i>
                        New Enlistments
                    </h6>
                    <h2 class="display-6 fw-bold text-primary">
                        {{ number_format($dashboard['personnel']['new_enlistments']) }}
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold"
                        style="text-decoration: underline dotted; cursor: help;"
                        data-bs-toggle="tooltip"
                        data-bs-title="Active members with no system activity in this reporting window.">
                        <i class="fa-fw fa-solid fa-ghost me-1"></i>
                        Ghost Count
                    </h6>
                    <h2 class="display-6 fw-bold text-secondary">
                        {{ number_format($dashboard['personnel']['ghost_count']) }}
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold"
                        style="text-decoration: underline dotted; cursor: help;"
                        data-bs-toggle="tooltip"
                        data-bs-title="Total individual trooper appearances at events.">
                        <i class="fa-fw fa-solid fa-calendar-check me-1"></i>
                        Attendance
                    </h6>
                    <h2 class="display-6 fw-bold">
                        {{ number_format($dashboard['personnel']['attendance_count']) }}
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
                <div class="card-body">
                    <h6 class="text-danger text-uppercase small fw-bold"
                        style="text-decoration: underline dotted; cursor: help;"
                        data-bs-toggle="tooltip"
                        data-bs-title="Active members who did not attend an event in this reporting window.">
                        <i class="fa-fw fa-solid fa-triangle-exclamation me-1"></i>
                        Attrition Risk
                    </h6>
                    <h2 class="display-6 fw-bold text-danger">
                        {{ number_format($dashboard['personnel']['attrition_risk']) }}
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold"
                        style="text-decoration: underline dotted; cursor: help;"
                        data-bs-toggle="tooltip"
                        data-bs-title="Percentage of events successfully completed.">
                        <i class="fa-fw fa-solid fa-check-to-slot me-1"></i>
                        Fulfillment
                    </h6>
                    <h2 class="display-6 fw-bold text-success">
                        {{ number_format($dashboard['logistics']['fulfillment_rate'], 1) }}%
                    </h2>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-fw fa-solid fa-hand-holding-dollar me-2 text-primary">
                        </i>
                        Galactic Impact
                    </h5>
                </div>
                <div class="card-body border-top">
                    <div class="row text-center">
                        <div class="col-4 border-end">
                            <p class="text-muted mb-1 small"
                               data-bs-toggle="tooltip"
                               data-bs-title="Combined direct and indirect funds raised for charity during events."
                               style="text-decoration: underline dotted; cursor: help;">
                                Total Credits
                            </p>
                            <h4 class="fw-bold mb-0 text-success">
                                ${{ number_format($dashboard['impact']['total_credits'], 2) }}
                            </h4>
                        </div>
                        <div class="col-4 border-end">
                            <p class="text-muted mb-1 small"
                               data-bs-toggle="tooltip"
                               data-bs-title="Total volunteer-hours contributed by all participating troopers."
                               style="text-decoration: underline dotted; cursor: help;">
                                Volunteer Hours
                            </p>
                            <h4 class="fw-bold mb-0">
                                {{ number_format($dashboard['impact']['volunteer_hours']) }}h
                            </h4>
                        </div>
                        <div class="col-4">
                            <p class="text-muted mb-1 small"
                               data-bs-toggle="tooltip"
                               data-bs-title="Donations made internally by troopers to support organization goals."
                               style="text-decoration: underline dotted; cursor: help;">
                                Internal Support
                            </p>
                            <h4 class="fw-bold mb-0">
                                ${{ number_format($dashboard['impact']['internal_donations'], 2) }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100 bg-dark text-white">
                <div class="card-body d-flex flex-column justify-content-around">
                    {{--
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-uppercase opacity-75"
                              data-bs-toggle="tooltip"
                              data-bs-title="Percentage of members who have opened and read organization notices."
                              style="text-decoration: underline dotted; cursor: help;">
                            Notice Read Rate
                        </span>
                        <span class="fw-bold">
                            {{ number_format($dashboard['engagement']['notice_penetration'], 1) }}%
                        </span>
                    </div>
                    <div class="progress mt-2 mb-3"
                         style="height: 6px;">
                        <div class="progress-bar bg-info"
                             style="width: {{ $dashboard['engagement']['notice_penetration'] }}%">
                        </div>
                    </div>
                    --}}
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small"
                              data-bs-toggle="tooltip"
                              data-bs-title="Number of merit-based awards issued to troopers in this reporting window."
                              style="text-decoration: underline dotted; cursor: help;">
                            <i class="fa-fw fa-solid fa-medal me-2 text-warning">
                            </i>
                            Awards Issued
                        </span>
                        <span class="fw-bold">
                            {{ $dashboard['engagement']['award_velocity'] }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small"
                              data-bs-toggle="tooltip"
                              data-bs-title="Amount of gallery activity, measured by events with new photo uploads."
                              style="text-decoration: underline dotted; cursor: help;">
                            <i class="fa-fw fa-solid fa-camera me-2 text-info">
                            </i>
                            Photos Tagged
                        </span>
                        <span class="fw-bold">
                            {{ $dashboard['engagement']['photo_activity'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-fw fa-solid fa-trophy me-2 text-warning">
                        </i>
                        Unit Performance Leaderboard
                    </h5>
                    <small class="text-muted">
                        Ranked by Funds Raised
                    </small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-uppercase small">
                            <tr>
                                <th class="ps-4"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="Primary Hosting Organization."
                                    style="text-decoration: underline dotted; cursor: help;">
                                    Hosting Organization
                                </th>
                                <th class="text-center"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="Successful 'Closed' status events hosted by this organization."
                                    style="text-decoration: underline dotted; cursor: help;">
                                    Events
                                </th>
                                <th class="text-center"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="Total Trooper count who 'Attended' events hosted by this organization (does not take into account costume worn)."
                                    style="text-decoration: underline dotted; cursor: help;">
                                    Troopers
                                </th>
                                <th class="text-end pe-4"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="Total direct financial contribution attributed to this organization's efforts."
                                    style="text-decoration: underline dotted; cursor: help;">
                                    Direct
                                </th>
                                <th class="text-end pe-4"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="Total indirect financial contribution attributed to this organization's efforts."
                                    style="text-decoration: underline dotted; cursor: help;">
                                    Indirect
                                </th>
                                <th class="text-end pe-4"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="Total financial contribution attributed to this organization's efforts."
                                    style="text-decoration: underline dotted; cursor: help;">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dashboard['leaderboard'] as $org)
                                <tr>
                                    <td class="ps-4 fw-bold text-white">
                                        {{ $org['name'] }}
                                    </td>
                                    <td class="text-center">
                                        <x-number-format :value="$org['events_completed']" />
                                    </td>
                                    <td class="text-center">
                                        <x-number-format :value="$org['troopers_attended']" />
                                    </td>
                                    <td class="text-end pe-4 fw-bold text-success">
                                        <x-number-format :value="$org['direct_funds_raised']"
                                                         :prefix="'$'"
                                                         :decimals="2" />
                                    </td>
                                    <td class="text-end pe-4 fw-bold text-success">
                                        <x-number-format :value="$org['indirect_funds_raised']"
                                                         :prefix="'$'"
                                                         :decimals="2" />
                                    </td>
                                    <td class="text-end pe-4 fw-bold text-success">
                                        <x-number-format :value="$org['total_funds_raised']"
                                                         :prefix="'$'"
                                                         :decimals="2" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
    });
</script>
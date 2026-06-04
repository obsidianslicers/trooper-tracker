{{-- Shared partial: rendered inline (HTMX) or included by costume.blade.php --}}

<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card shadow-sm border-0 text-center py-3">
            <div class="h3 fw-bold text-primary mb-0">
                {{ number_format($stats['total_deployments']) }}
            </div>
            <div class="text-uppercase text-muted small fw-bold">Total Deployments</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card shadow-sm border-0 text-center py-3">
            <div class="h3 fw-bold text-info mb-0">
                {{ number_format($stats['unique_troopers']) }}
            </div>
            <div class="text-uppercase text-muted small fw-bold">Unique Troopers</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card shadow-sm border-0 text-center py-3">
            <div class="h3 fw-bold text-success mb-0">
                {{ $stats['last_deployed_at'] ? $stats['last_deployed_at']->format('M j, Y') : '—' }}
            </div>
            <div class="text-uppercase text-muted small fw-bold">Last Deployed</div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-dark text-white text-uppercase small fw-bold">
        <i class="fa-solid fa-bolt me-2"></i> Top Troopers — {{ $costume->name }}
    </div>
    @if($top_troopers->isNotEmpty())
        @php $max_troops = $top_troopers->first()->troop_count ?? 1; @endphp
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr class="text-uppercase small text-muted">
                        <th scope="col" class="ps-3" style="width: 4rem;">Rank</th>
                        <th scope="col">Trooper</th>
                        <th scope="col" class="text-end" style="width: 8rem;">Troops</th>
                        <th scope="col" class="pe-3" style="width: 35%;">Activity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($top_troopers as $op)
                        <tr>
                            <td class="ps-3 text-muted fw-bold">#{{ $loop->iteration }}</td>
                            <td>
                                <a href="{{ route('service-records.trooper', ['trooper' => $op->trooper]) }}"
                                   class="fw-bold text-uppercase text-decoration-none">
                                    {{ $op->trooper->display_name }}
                                </a>
                            </td>
                            <td class="text-end text-primary fw-bold">
                                {{ $op->troop_count }}
                                <small class="text-muted" style="font-size: 0.6rem;">TROOPS</small>
                            </td>
                            <td class="pe-3">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-success"
                                         role="progressbar"
                                         style="width: {{ ($op->troop_count / $max_troops) * 100 }}%"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="card-body">
            <p class="text-muted mb-0">No trooper data found for the selected filters.</p>
        </div>
    @endif
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h6 class="text-muted text-uppercase small fw-bold mb-3">
            Service Summary
        </h6>
        <div class="row g-2">
            <div class="col-6">
                <div class="p-3 border rounded text-center">
                    <div class="h3 mb-0">
                        <x-number-format :value="$service_summary['total_shifts']" />
                    </div>
                    <div class="small text-muted">Troops/Missions</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 border rounded text-center">
                    <div class="h3 mb-0">
                        <x-number-format :value="$service_summary['total_hours']" />
                    </div>
                    <div class="small text-muted">Mission Hours</div>
                </div>
            </div>
        </div>
        <table class="table table-sm mt-3 mb-0">
            <tbody>
                <tr class="small">
                    <td class="text-muted border-0">Trooper Position</td>
                    <td class="text-end fw-bold border-0">
                        #{{ $service_summary['rank'] }}
                    </td>
                </tr>
                <tr class="small">
                    <td class="text-muted border-0">Direct Funds</td>
                    <td class="text-end fw-bold border-0">
                        <x-number-format :value="$service_summary['direct_funds']"
                                         :prefix="'$'" />
                    </td>
                </tr>
                <tr class="small">
                    <td class="text-muted border-0">Indirect Funds</td>
                    <td class="text-end fw-bold border-0">
                        <x-number-format :value="$service_summary['indirect_funds']"
                                         :prefix="'$'" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-dark text-white">
        <h5 class="card-title mb-0">
            Achievements
        </h5>
    </div>
    <ul class="list-group list-group-flush">
        @foreach($service_summary['milestones'] as $milestone)
            <li class="list-group-item d-flex align-items-center {{ $milestone['is_earned'] ? '' : 'bg-light opacity-50' }}">
                <span
                      class="badge d-inline-flex align-items-center gap-2 {{ $milestone['type'] == \App\Enums\AchievementType::TROOPED_501 ? 'text-success' : '' }}">
                    <i class="fa-solid fa-fw {{ $milestone['icon'] }}"></i>
                    {{ $milestone['title'] }}
                </span>
            </li>
        @endforeach
    </ul>
</div>
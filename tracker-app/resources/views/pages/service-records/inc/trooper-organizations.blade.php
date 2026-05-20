@if($trooper_organizations->isNotEmpty())

    <!-- 1. DESKTOP VIEW: Clean, Structured Data Table (Visible on MD screens and up) -->
    <div class="d-none d-md-block table-responsive">
        <x-table>
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>Organization</th>
                    <th>Identifier</th>
                    <th>Membership ID</th>
                    <th>Assignment Location</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($trooper_organizations as $trooper_organization)
                    <tr>
                        <td>
                            <x-logo :storage_path="$trooper_organization->image_path_sm ?? ''"
                                    :default_path="'img/icons/organization-32x32.png'"
                                    :width="32"
                                    :height="32" />
                        </td>
                        <td class="fw-semibold">
                            {{ $trooper_organization->name }}
                        </td>
                        <td>
                            {{ $trooper_organization->identifier_display }}
                        </td>
                        <td class="font-monospace text-warning">
                            {{ $trooper_organization->pivot->identifier }}
                        </td>
                        <td>
                            {{ $trooper_organization->assignment->organization->name ?? 'Unassigned' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </div>

    <!-- 2. MOBILE VIEW: Tactical Stacked Cards (Visible only on screens smaller than MD) -->
    <div class="d-block d-md-none">
        <div class="row g-3">
            @foreach ($trooper_organizations as $trooper_organization)
                <div class="col-12">
                    <div class="card bg-dark border-secondary shadow-sm">
                        <div class="card-body p-3">

                            <!-- Top Row: Logo & Organization Title -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0 bg-secondary-subtle p-1 rounded">
                                    <x-logo :storage_path="$trooper_organization->image_path_sm ?? ''"
                                            :default_path="'img/icons/organization-32x32.png'"
                                            :width="36"
                                            :height="36" />
                                </div>
                                <div class="flex-grow-1 min-w-0 ms-3">
                                    <small class="text-muted d-block text-uppercase fw-bold text-xs">Organization</small>
                                    <span class="h6 mb-0 fw-bold text-white text-truncate d-block">
                                        {{ $trooper_organization->name }}
                                    </span>
                                </div>
                            </div>

                            <!-- Middle Section: Identifier Matrix Box -->
                            <div class="bg-secondary-subtle p-2 rounded mb-2 d-flex justify-content-between align-items-center">
                                <div class="min-w-0">
                                    <small class="text-muted d-block text-uppercase fw-bold mb-0.5">
                                        {{ $trooper_organization->identifier_display }}
                                    </small>
                                    <span class="font-monospace fw-bold text-warning small text-break">
                                        {{ $trooper_organization->pivot->identifier }}
                                    </span>
                                </div>
                            </div>

                            <!-- Bottom Row: Unit Assignment Info -->
                            <div class="pt-2 border-top border-secondary mt-2">
                                <small class="text-muted d-block text-uppercase fw-bold text-xs mb-0.5">Assignment</small>
                                <span class="small text-white text-break">
                                    <i class="fa-solid fa-location-dot me-1 text-muted small"></i>
                                    {{ $trooper_organization->assignment->organization->name ?? 'Unassigned' }}
                                </span>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

@else
    <!-- 3. EMPTY STATE FALLBACK: Cleared component container alignment -->
    <x-table>
        <tbody>
            <x-table-empty :colspan="5">
                No Organization Memberships ... Yet!
            </x-table-empty>
        </tbody>
    </x-table>
@endif
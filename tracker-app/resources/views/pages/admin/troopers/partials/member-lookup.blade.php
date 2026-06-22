@if($identifier === null)
    {{-- No identifier provided; nothing to look up --}}
@elseif($duplicate !== null)
    <div class="alert alert-danger d-flex gap-2 align-items-start py-2 px-3 mt-2 mb-0 small">
        <i class="fa-solid fa-triangle-exclamation mt-1 flex-shrink-0"></i>
        <div>
            <div class="fw-semibold">Duplicate identifier</div>
            <div>
                <a href="{{ route('admin.troopers.profile', $duplicate) }}" target="_blank">
                    {{ $duplicate->display_name }}
                </a>
                already holds <strong>{{ $identifier }}</strong>.
            </div>
        </div>
    </div>
@elseif(!$has_lookup_service)
    {{-- Org has no lookup service configured; nothing to show --}}
@elseif($member !== null)
    <div class="border rounded p-2 mt-2 d-flex gap-2 align-items-start small
                {{ $member['is_approved'] ? 'border-success-subtle bg-success-subtle' : 'border-warning-subtle bg-warning-subtle' }}">
        @if($member['thumbnail_url'])
            <img src="{{ $member['thumbnail_url'] }}"
                 alt="{{ $member['full_name'] }}"
                 class="rounded flex-shrink-0"
                 style="width:40px;height:40px;object-fit:cover;">
        @else
            <i class="fa-solid fa-user-astronaut fa-xl mt-1 flex-shrink-0
                      {{ $member['is_approved'] ? 'text-success' : 'text-warning' }}"></i>
        @endif
        <div class="flex-grow-1 min-width-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                <span class="fw-semibold">{{ $member['full_name'] ?? $member['formatted_identifier'] }}</span>
                <div class="d-flex gap-1">
                    @if($member['status'])
                        <span class="badge {{ $member['status'] === 'Active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $member['status'] }}
                        </span>
                    @endif
                    @if($member['standing'])
                        <span class="badge {{ $member['standing'] === 'Good' ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $member['standing'] }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="text-muted">{{ $member['formatted_identifier'] }}{{ $member['unit_name'] ? ' &mdash; ' . $member['unit_name'] : '' }}</div>
            @if($member['profile_url'])
                <a href="{{ $member['profile_url'] }}" target="_blank" rel="noopener noreferrer" class="small">
                    View profile <i class="fa-solid fa-arrow-up-right-from-square fa-xs"></i>
                </a>
            @endif
        </div>
    </div>
@else
    <div class="alert alert-warning d-flex gap-2 align-items-start py-2 px-3 mt-2 mb-0 small">
        <i class="fa-solid fa-circle-question mt-1 flex-shrink-0"></i>
        <div>
            <span class="fw-semibold">Not found</span> &mdash;
            No member with identifier <strong>{{ $identifier }}</strong>
            @if($org_name) was found in {{ $org_name }}.@else was found.@endif
        </div>
    </div>
@endif

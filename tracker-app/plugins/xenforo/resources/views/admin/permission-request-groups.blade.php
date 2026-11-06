@if(!$has_account)
    <p class="text-muted small">
        This trooper has no linked forum account, so forum groups cannot be requested.
    </p>
@else
    <div class="row">
        @foreach([
            ['title' => 'Add Forum Groups', 'field' => 'groups_to_add', 'groups' => $addable,
                'empty' => 'No other forum groups are available.'],
            ['title' => 'Remove Forum Groups', 'field' => 'groups_to_remove', 'groups' => $current,
                'empty' => 'This trooper is not in any requestable forum groups.'],
        ] as $column)
            <div class="col-md-6 mb-3">
                <h6>{{ $column['title'] }}</h6>
                @forelse($column['groups'] as $group)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               id="xenforo-{{ $column['field'] }}-{{ $group['id'] }}"
                               name="{{ $prefix }}[{{ $column['field'] }}][]"
                               value="{{ $group['id'] }}">
                        <label class="form-check-label d-inline-flex align-items-center gap-2"
                               for="xenforo-{{ $column['field'] }}-{{ $group['id'] }}">
                            @if(!empty($group['banner_image_url']))
                                <img class="forum-group-badge" src="{{ $group['banner_image_url'] }}" alt="" loading="lazy">
                            @endif
                            <span>{{ $group['title'] }}</span>
                        </label>
                    </div>
                @empty
                    <p class="text-muted small">{{ $column['empty'] }}</p>
                @endforelse
            </div>
        @endforeach
    </div>
@endif

<x-transmission-bar :id="'trooper-picker'" />
<form method="GET"
      class="input-group mb-4"
      hx-get="{{ route('pickers.trooper') }}"
      hx-trigger="input changed delay:500ms from:input[name=search_term]"
      hx-select="#trooper-search-results"
      hx-target="#trooper-search-results"
      hx-swap="outerHTML"
      hx-indicator="#transmission-bar-trooper-picker">
    @foreach (qs() as $key => $value)
        <x-input-hidden :property="$key"
                        :value="$value" />
    @endforeach
    <input type="text"
           name="search_term"
           placeholder="Search Name, Username, Email (at least 3 chars)"
           class="form-control rounded-start"
           value="{{ $search_term }}" />

    <button type="submit"
            class="btn btn-outline-secondary"
            hx-trigger="click from:closest form">
        <i class="fa fa-fw fa-search"></i>
    </button>
</form>
<ul id="trooper-search-results"
    class="list-group list-group-flush">
    @forelse($troopers as $trooper)
        <li class="list-group-item pointer"
            data-property="{{ $property }}"
            data-id="{{ $trooper->id }}"
            data-name="{{ $trooper->display_name }}"
            data-is-visitor="{{ $trooper->is_visitor ? '1' : '0' }}"
            data-event="{{ $event_name }}">
            {{ $trooper->display_name }}
            @if(!empty($trooper->legal_name) && $trooper->legal_name !== $trooper->display_name)
                <div class="text-muted small">
                    {{ $trooper->legal_name }}
                </div>
            @endif
            @foreach($trooper->organizations as $org)
                @if($org->pivot->identifier)
                    <span class="badge bg-secondary me-1" title="{{ $org->name }}">
                        {{ $org->identifier_display ?? $org->name }}: {{ $org->pivot->identifier }}
                    </span>
                @endif
            @endforeach
        </li>
    @empty
        <li class="list-group-item">
            No troopers could be found.
            @if($search_term && now()->year == 2026)
                <p class="text-muted">
                    This may be because of the conversion to the new Troop Tracker.
                    If you are searching for someone and they cannot be found since conversion,
                    have they completed their setup yet?
                </p>
            @endif
        </li>
    @endforelse
</ul>
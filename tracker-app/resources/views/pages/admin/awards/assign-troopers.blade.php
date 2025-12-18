@extends('layouts.base')

@section('page-title', 'Assign Award to Troopers')

@section('content')

<x-slim-container>

    <x-card>
        <form method="GET" action="{{ route('admin.awards.assign-troopers', $award) }}" class="mb-3">
            <x-input-container>
                <x-label>
                    Search Troopers:
                </x-label>
                <x-input-text :property="'search'" :value="$search ?? ''" placeholder="Enter trooper name..." />
            </x-input-container>
            <button type="submit" class="btn btn-primary">Search</button>
            @if($search)
                <a href="{{ route('admin.awards.assign-troopers', $award) }}" class="btn btn-secondary">Clear Search</a>
            @endif
        </form>

        <form method="POST"
              novalidate="novalidate">
            @csrf

            <x-input-container>
                <x-label>
                    Award:
                </x-label>
                <x-input-text :property="'award_name'"
                              :disabled="true"
                              :value="$award->name" />
            </x-input-container>

            <x-input-container>
                <x-label>
                    Award Date:
                </x-label>
                <x-input-date :property="'award_date'"
                              :value="now()->format('Y-m-d')" />
            </x-input-container>

            <x-input-container>
                <x-label>
                    Select Troopers:
                </x-label>
                <div class="row">
                    @forelse($troopers as $trooper)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="trooper_ids[]" value="{{ $trooper->id }}" id="trooper_{{ $trooper->id }}"
                                       {{ $trooper->awards->contains('id', $award->id) ? 'checked' : '' }}>
                                <label class="form-check-label" for="trooper_{{ $trooper->id }}">
                                    {{ $trooper->name }}
                                </label>
                            </div>
                        </div>
                    @empty
                        <p>No troopers found.</p>
                    @endforelse
                </div>
            </x-input-container>

            <x-submit-container>
                <x-submit-button>Assign Award</x-submit-button>
                <x-link-button-cancel :url="route('admin.awards.list-troopers', $award)" />
            </x-submit-container>

            @if(!$search)
                {{ $troopers->links() }}
            @endif

        </form>
    </x-card>
</x-slim-container>

@endsection
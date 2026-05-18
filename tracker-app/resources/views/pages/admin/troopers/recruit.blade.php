@extends('layouts.base')

@section('page-title', 'Recruit Trooper')

@section('content')

    <x-slim-container>

        <x-card>

            <form method="POST"
                  action="{{ route('admin.troopers.recruit') }}"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        Trooper
                    </x-label>
                    <x-input-picker :property="'trooper_id'"
                                    :route="'pickers.trooper'"
                                    :params="['moderated_only' => false, 'active_only' => true]"
                                    :text="old('trooper_name', '(Search for a Trooper)')"
                                    :value="old('trooper_id')" />
                    <x-input-error :property="'trooper_id'" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Assign to Unit
                    </x-label>
                    <select name="organization_id"
                            class="form-select @error('organization_id') is-invalid @enderror">
                        <option value="">— Select a Unit —</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}"
                                    @selected(old('organization_id') == $org->id)>
                                {{ $org->parent?->name ? $org->parent->name . ' — ' : '' }}{{ $org->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :property="'organization_id'" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Club Identifier <span class="text-muted fw-normal">(e.g. TK number — optional)</span>
                    </x-label>
                    <x-input-text :property="'identifier'"
                                  :value="old('identifier')"
                                  :placeholder="'TK0000'" />
                    <x-input-error :property="'identifier'" />
                </x-input-container>

                <x-modal-picker :label="'Find a Trooper'" />

                <x-submit-container>
                    <x-submit-button>Add to Roster</x-submit-button>
                    <x-link-button-cancel :url="route('admin.troopers.list')" />
                </x-submit-container>

            </form>

        </x-card>

    </x-slim-container>

@endsection

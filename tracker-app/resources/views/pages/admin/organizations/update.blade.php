@extends('layouts.base')

@section('page-title', 'Update Organization')

@section('content')

    @include('pages.admin.organizations.tabs', compact('organization'))

    <x-transmission-bar :id="'organization'" />

    <x-slim-container>

        <x-card>
            <div class="row">
                <div class="col-md-12 col-lg-8">

                    <form method="POST"
                          novalidate="novalidate">
                        @csrf

                        @isset($organization->parent)
                            <x-input-container>
                                <x-label>
                                    Parent {{ $organization->parent->type->name }}:
                                </x-label>
                                <x-input-hidden :property="'parent_id'"
                                                :value="$organization->parent_id" />
                                <x-input-text :property="'parent_name'"
                                              :disabled="true"
                                              :value="$organization->parent->name" />
                            </x-input-container>
                        @endisset

                        @if($organization->type == \App\Enums\OrganizationType::UNIT)
                            @can('update', $organization->parent)
                                {{-- TODO MOVE PARENTS --}}
                            @endcan
                        @endif

                        <x-input-container>
                            <x-label>
                                Name:
                            </x-label>
                            <x-input-text :property="'name'"
                                          :value="$organization->name" />
                        </x-input-container>

                        <x-input-container>
                            <x-label>
                                Google Sync Sheet ID:
                            </x-label>
                            <x-input-text :property="'sync_sheet_id'"
                                          :value="$organization->sync_sheet_id" />
                            <p class="form-help">
                                <i class="text-muted">Optional Google Sheet ID used for sync.</i>
                            </p>
                        </x-input-container>

                        <x-input-container>
                            <x-label>
                                Discord Mention:
                            </x-label>
                            <x-input-text :property="'discord_mention'"
                                          :value="$organization->discord_mention" />
                            <p class="form-help">
                                <i class="text-muted">Optional Discord role/user mention (e.g. <code>&lt;@&amp;123456789&gt;</code>).</i>
                            </p>
                        </x-input-container>

                        <x-input-container>
                            <x-label>
                                Related Forum (Node ID):
                            </x-label>
                            <x-input-text :property="'related_forum'"
                                          :value="$organization->related_forum" />
                            <p class="form-help">
                                <i class="text-muted">Optional XenForo forum node id where event threads should be created.</i>
                            </p>
                        </x-input-container>

                        <x-input-container>
                            <x-label>
                                Related Forum Archive (Node ID):
                            </x-label>
                            <x-input-text :property="'related_forum_archive'"
                                          :value="$organization->related_forum_archive" />
                            <p class="form-help">
                                <i class="text-muted">Optional XenForo archive forum node id for moved/archived threads.</i>
                            </p>
                        </x-input-container>

                        <x-submit-container>
                            <x-submit-button>
                                Update
                            </x-submit-button>
                            <x-link-button-cancel :url="route('admin.organizations.list')" />
                        </x-submit-container>

                    </form>

                </div>
                <div class="col-md-12 col-lg-4">

                    <div class="row">
                        <div class="col-sm-12 text-center">
                            @include('pages.admin.organizations.image', compact('organization'))
                        </div>
                        <div class="col-sm-12">
                            <p class="form-help text-center">
                                <i class="form-help text-muted">
                                    The uploaded image will be resized to large 128x128 and small 32x32.
                                </i>
                            </p>
                        </div>
                    </div>
                </div>

            </div>


            <x-trooper-stamps :model="$organization" />
        </x-card>

    </x-slim-container>

@endsection
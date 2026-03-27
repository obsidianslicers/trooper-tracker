@php
    $posts = $xenforoThreadPosts ?? [];
@endphp

@if(!empty($posts))
    <div class="mt-5">
        <x-section-title>Forum Comments</x-section-title>

        <div class="list-group">
            @foreach($posts as $post)
                <div class="list-group-item">
                    <div class="d-flex align-items-start gap-3">
                        @if(!empty($post['avatar_url']))
                            <img src="{{ $post['avatar_url'] }}"
                                 alt="{{ $post['username'] }} avatar"
                                 width="32"
                                 height="32"
                                 class="rounded-circle border" />
                        @else
                            <div class="rounded-circle border bg-light" style="width:32px;height:32px"></div>
                        @endif

                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $post['username'] ?? 'Unknown' }}</strong>
                                    @if(!empty($post['post_date']))
                                        <span class="text-muted small ms-2">
                                            {{ \Carbon\Carbon::createFromTimestamp((int) $post['post_date'])->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>
                                @if(!empty($post['post_url']))
                                    <a href="{{ $post['post_url'] }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="btn btn-sm btn-outline-secondary"
                                       title="View on forum">
                                        <i class="fa fa-fw fa-external-link-alt"></i>
                                    </a>
                                @endif
                            </div>

                            @if(!empty($post['message_html']))
                                <div class="mt-2 text-body">
                                    {!! $post['message_html'] !!}
                                </div>
                            @elseif(!empty($post['message']))
                                <div class="mt-2 text-body">
                                    {{ \Illuminate\Support\Str::limit($post['message'], 400) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if(!empty($xenforoBaseUrl) && !empty($event->thread_id))
            <div class="mt-3">
                <a href="{{ $xenforoBaseUrl.'/threads/'.$event->thread_id.'/' }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn btn-outline-secondary">
                    View full thread
                </a>
            </div>
        @endif
    </div>
@endif

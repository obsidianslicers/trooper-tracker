@php
    $comments = app(\TroopTracker\Plugins\Xenforo\Threads\EventComments::class);
    $thread = $comments->thread($event);
    $posts = $comments->posts($thread);
    $hasThread = $thread !== null;
    $smilies = $hasThread ? $comments->smilies() : [];
    $xenforoBaseUrl = $comments->baseUrl();
@endphp

@if(!empty($posts) || $hasThread)
    <div id="forum-comments"
         class="mt-5">
        <x-section-title>{{ __('xenforo::events.Forum Comments') }}</x-section-title>

        @if(!empty($posts))
            <div class="list-group mb-3">
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
                                <div class="rounded-circle border bg-light"
                                     style="width:32px;height:32px"></div>
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
                                           title="{{ __('xenforo::events.View on forum') }}">
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
        @endif

        @if($hasThread)
            {{-- sent by the event page (PluginSlot.svelte) without reloading it --}}
            <form method="POST"
                  action="{{ route('events.forum-reply', compact('event')) }}"
                  data-inertia-form>
                @csrf
                <div class="row mb-2">
                    <div class="col">
                        <textarea name="message"
                                  id="forum-reply-message"
                                  class="form-control"
                                  rows="3"
                                  placeholder="{{ __('xenforo::events.Write a reply...') }}"
                                  maxlength="10000"
                                  required></textarea>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        @if(!empty($smilies))
                            <details class="forum-reply-smilies">
                                <summary class="btn btn-sm btn-outline-secondary">
                                    <i class="far fa-smile me-1"></i>{{ __('components.Smilies') }}
                                </summary>
                                <div class="mt-2 p-2 border rounded"
                                     style="max-height: 180px; overflow-y: auto; line-height: 2.2;">
                                    @foreach($smilies as $smiley)
                                        @php $code = $smiley['smilie_text'][0] ?? ''; @endphp
                                        @if($code !== '' && !empty($smiley['image_url']))
                                            <img src="{{ $smiley['image_url'] }}"
                                                 alt="{{ $smiley['title'] }}"
                                                 title="{{ $smiley['title'] }} — {{ $code }}"
                                                 class="forum-smiley me-1"
                                                 style="height:1.6rem; cursor:pointer;"
                                                 data-code="{{ $code }}"
                                                 onclick="const t = document.getElementById('forum-reply-message'); t.setRangeText(this.dataset.code, t.selectionStart, t.selectionEnd, 'end'); t.focus();" />
                                        @endif
                                    @endforeach
                                </div>
                            </details>
                        @endif
                        <div class="form-check mb-0">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="notify_command_staff"
                                   value="1"
                                   id="notify-command-staff">
                            <label class="form-check-label text-muted small" for="notify-command-staff">
                                <i class="fa fa-fw fa-bell me-1"></i>{{ __('xenforo::events.Notify command staff and the event coordinator') }}
                            </label>
                        </div>
                    </div>
                    <button type="submit"
                            class="btn btn-primary">
                        {{ __('xenforo::events.Post Reply') }}
                    </button>
                </div>
            </form>
        @endif

        @if(!empty($xenforoBaseUrl) && $hasThread)
            <div class="row mt-3">
                <div class="col-12 d-flex flex-column flex-md-row justify-content-md-end align-items-md-center gap-2">
                    <a href="{{ $xenforoBaseUrl . '/threads/' . $thread->thread_id . '/' }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="btn btn-outline-secondary">
                        {{ __('xenforo::events.View full thread') }}
                    </a>
                </div>
            </div>
        @endif
    </div>
@endif
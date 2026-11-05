@php
    $thread = app(\TroopTracker\Plugins\Xenforo\Threads\EventComments::class)->thread($event);
@endphp
@if($thread?->hasFirstPost())
    <a href="{{ rtrim((string) config('services.xenforo.base_url'), '/') . '/posts/' . $thread->post_id . '/' }}"
       class="btn btn-outline-secondary"
       target="_blank"
       rel="noopener noreferrer"
       title="View Forum Post">
        <i class="fa fa-fw fa-comments"></i>
    </a>
@endif

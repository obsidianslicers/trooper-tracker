<script lang="ts">
    import type { PushNotification } from "../models";

    let {
        notification,
        onopen,
    }: {
        notification: PushNotification;
        onopen: (notification: PushNotification) => void;
    } = $props();
</script>

<button
    type="button"
    class="list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-3 text-start"
    class:notification-unread={notification.is_unread}
    class:fw-semibold={notification.is_unread}
    onclick={() => onopen(notification)}
>
    <div>
        <div class="d-flex align-items-center gap-2">
            <span class="unread-dot" class:bg-primary={notification.is_unread}></span>
            <span>{notification.title}</span>
        </div>
        <div class="text-muted fw-normal small mt-1 ms-3 text-break">{notification.body}</div>
    </div>
    <small class="text-muted text-nowrap fw-normal">{notification.sent_ago}</small>
</button>

<style>
    .unread-dot {
        display: inline-block;
        flex-shrink: 0;
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
</style>

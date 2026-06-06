<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\OauthProvider;
use App\Models\Event;
use App\Models\OauthLogin;
use App\Models\Trooper;
use App\Notifications\Events\EventForumPostNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEventForumPostNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Event $event,
        private readonly int $post_id,
        private readonly string $username,
        private readonly ?int $xenforo_user_id,
        private readonly string $message,
    ) {}

    public function handle(): void
    {
        $poster_trooper_id = $this->resolvePosterTrooperId();

        $notification = new EventForumPostNotification(
            $this->event,
            $this->post_id,
            $this->username,
            $this->message,
        );

        Trooper::whereHas('event_watches', fn ($q) => $q->where('event_id', $this->event->id))
            ->when($poster_trooper_id !== null, fn ($q) => $q->where('id', '!=', $poster_trooper_id))
            ->each(fn (Trooper $trooper) => $trooper->notify($notification));
    }

    private function resolvePosterTrooperId(): ?int
    {
        if ($this->xenforo_user_id === null)
        {
            return null;
        }

        $oauth = OauthLogin::where(OauthLogin::PROVIDER, OauthProvider::XENFORO)
            ->where(OauthLogin::PROVIDER_ID, (string) $this->xenforo_user_id)
            ->first();

        return $oauth?->trooper_id;
    }
}

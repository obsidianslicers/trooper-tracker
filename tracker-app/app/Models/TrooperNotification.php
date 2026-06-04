<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Extends Laravel's DatabaseNotification to use the project's tt_notifications table
 * and provide convenience accessors for title, body, url, and unread status.
 */
class TrooperNotification extends DatabaseNotification
{
    use HasFactory;

    protected $table = 'tt_notifications';

    public function getTitleAttribute(): string
    {
        return $this->data['title'] ?? '';
    }

    public function getBodyAttribute(): string
    {
        return $this->data['body'] ?? '';
    }

    public function getUrlAttribute(): string
    {
        return $this->data['url'] ?? '/events';
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}

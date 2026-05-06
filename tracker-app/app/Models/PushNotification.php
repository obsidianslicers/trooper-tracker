<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $trooper_id
 * @property string $title
 * @property string $body
 * @property string $url
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Trooper $trooper
 */
class PushNotification extends Model
{
    const ID = 'id';
    const TROOPER_ID = 'trooper_id';
    const TITLE = 'title';
    const BODY = 'body';
    const URL = 'url';
    const READ_AT = 'read_at';

    protected $table = 'tt_push_notifications';

    protected $casts = [
        self::READ_AT => 'datetime',
    ];

    protected $fillable = [
        self::TROOPER_ID,
        self::TITLE,
        self::BODY,
        self::URL,
        self::READ_AT,
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markRead(): void
    {
        if ($this->read_at === null) {
            $this->update([self::READ_AT => now()]);
        }
    }
}

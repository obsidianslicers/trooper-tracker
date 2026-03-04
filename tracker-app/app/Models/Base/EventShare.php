<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Event;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EventShare
 * 
 * @property int $id
 * @property int $event_id
 * @property int $trooper_id
 * @property string $share_token
 * @property string $recipient_email
 * @property int $view_count
 * @property Carbon $expires_at
 * @property bool $is_revoked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Event $event
 * @property Trooper $trooper
 *
 * @package App\Models\Base
 */
class EventShare extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const EVENT_ID = 'event_id';
    const TROOPER_ID = 'trooper_id';
    const SHARE_TOKEN = 'share_token';
    const RECIPIENT_EMAIL = 'recipient_email';
    const VIEW_COUNT = 'view_count';
    const EXPIRES_AT = 'expires_at';
    const IS_REVOKED = 'is_revoked';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_event_shares';

    protected $casts = [
        self::ID => 'int',
        self::EVENT_ID => 'int',
        self::TROOPER_ID => 'int',
        self::VIEW_COUNT => 'int',
        self::EXPIRES_AT => 'datetime',
        self::IS_REVOKED => 'bool',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $hidden = [
        self::SHARE_TOKEN
    ];

    protected $fillable = [
        self::EVENT_ID,
        self::TROOPER_ID,
        self::SHARE_TOKEN,
        self::RECIPIENT_EMAIL,
        self::VIEW_COUNT,
        self::EXPIRES_AT,
        self::IS_REVOKED
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}

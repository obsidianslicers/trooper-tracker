<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Models;

use App\Models\Event;
use App\Models\EventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An event type's own forums. Events of the type open their thread in its event
 * forum and archive it to its archive forum; either left empty falls back to the
 * hosting organization's forum.
 *
 * @property int $id
 * @property int $event_type_id
 * @property ?int $event_forum_id
 * @property ?int $archive_forum_id
 */
class EventTypeForum extends Model
{
    public const EVENT_TYPE_ID = 'event_type_id';

    public const EVENT_FORUM_ID = 'event_forum_id';

    public const ARCHIVE_FORUM_ID = 'archive_forum_id';

    public const SETTINGS = [self::EVENT_FORUM_ID, self::ARCHIVE_FORUM_ID];

    protected $table = 'tt_event_type_xenforo_forums';

    protected $fillable = [self::EVENT_TYPE_ID, ...self::SETTINGS];

    protected $casts = [
        self::EVENT_TYPE_ID => 'int',
        self::EVENT_FORUM_ID => 'int',
        self::ARCHIVE_FORUM_ID => 'int',
    ];

    public static function forEventType(?int $event_type_id): ?self
    {
        return $event_type_id === null
            ? null
            : static::query()->where(self::EVENT_TYPE_ID, $event_type_id)->first();
    }

    public static function forEvent(Event $event): ?self
    {
        return static::forEventType($event->event_type?->id);
    }

    /**
     * Save an event type's forums. Empty values clear them.
     *
     * @param  array<string, mixed>  $values
     */
    public static function store(int $event_type_id, array $values): self
    {
        $clean = [];

        foreach (array_intersect_key($values, array_flip(self::SETTINGS)) as $key => $value)
        {
            $clean[$key] = filled($value) ? (int) $value : null;
        }

        return static::query()->updateOrCreate([self::EVENT_TYPE_ID => $event_type_id], $clean);
    }

    public function event_type(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }
}

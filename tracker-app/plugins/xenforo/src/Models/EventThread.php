<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Models;

use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An event's forum thread, and whether one should be created when it's posted.
 *
 * An event with no row gets a thread.
 *
 * @property int $id
 * @property int $event_id
 * @property bool $should_create_thread
 * @property ?int $thread_id
 * @property ?int $post_id
 * @property-read Event $event
 */
class EventThread extends Model
{
    public const EVENT_ID = 'event_id';

    public const SHOULD_CREATE_THREAD = 'should_create_thread';

    public const THREAD_ID = 'thread_id';

    public const POST_ID = 'post_id';

    protected $table = 'tt_xenforo_event_threads';

    protected $fillable = [
        self::EVENT_ID,
        self::SHOULD_CREATE_THREAD,
        self::THREAD_ID,
        self::POST_ID,
    ];

    protected $casts = [
        self::EVENT_ID => 'int',
        self::SHOULD_CREATE_THREAD => 'bool',
        self::THREAD_ID => 'int',
        self::POST_ID => 'int',
    ];

    protected $attributes = [
        self::SHOULD_CREATE_THREAD => true,
    ];

    public static function forEvent(int $event_id): ?self
    {
        return static::query()->where(self::EVENT_ID, $event_id)->first();
    }

    public static function forThread(int $thread_id): ?self
    {
        return static::query()->where(self::THREAD_ID, $thread_id)->first();
    }

    /**
     * The event's thread, only while it is linked and threads are wanted for it.
     */
    public static function linked(int $event_id): ?self
    {
        $thread = static::forEvent($event_id);

        return $thread !== null && $thread->hasThread() ? $thread : null;
    }

    public static function shouldCreateFor(int $event_id): bool
    {
        return static::forEvent($event_id)?->should_create_thread ?? true;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function store(int $event_id, array $values): self
    {
        return static::query()->updateOrCreate([self::EVENT_ID => $event_id], $values);
    }

    public function hasThread(): bool
    {
        return $this->should_create_thread && !empty($this->thread_id);
    }

    public function hasFirstPost(): bool
    {
        return $this->hasThread() && !empty($this->post_id);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}

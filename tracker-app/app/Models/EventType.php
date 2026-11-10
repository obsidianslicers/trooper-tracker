<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Base\EventType as BaseEventType;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventTypeScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A kind of event (troop) that coordinators pick on the event form.
 *
 * Administrators manage the list: its order on the form, a description that helps
 * coordinators pick the right one, whether it is offered at all, and whether its
 * events count toward troopers' troop totals (milestones, leaderboards, service
 * records). Events reference a type by its slug, which never changes.
 */
class EventType extends BaseEventType
{
    use HasEventTypeScopes;
    use HasFactory;
    use HasTrooperStamps;

    public const DEFAULT_SLUG = 'regular';

    /**
     * What members and coordinators are told about events of a type that doesn't count,
     * word for word everywhere (the event list, event page, event form, service record,
     * forum thread and Discord). The admin Event Types page uses the same label.
     */
    public const NOT_COUNTED_LABEL = 'Non-troop-credit';

    public const NOT_COUNTED_NOTICE = 'Non-troop-credit event: Does not count toward troop totals.';

    /**
     * @return HasMany<Event, self>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * The type a new event gets unless it picks one: the first offered, in the
     * administrators' order (Regular, as seeded).
     */
    public static function defaultId(): ?int
    {
        return static::query()->active()->ordered()->value(self::ID);
    }

    /**
     * The id of the type with this name, such as one of the seeded defaults ("Regular").
     */
    public static function idNamed(string $name): ?int
    {
        return static::query()->where(self::NAME, $name)->value(self::ID);
    }

    /**
     * A slug for a new type's name that no other type uses.
     */
    public static function uniqueSlugFor(string $name): string
    {
        $base = Str::limit(Str::slug($name, ''), 28, '') ?: 'type';
        $slug = $base;
        $suffix = 2;

        while (static::query()->where(self::SLUG, $slug)->exists())
        {
            $slug = $base.$suffix++;
        }

        return $slug;
    }
}

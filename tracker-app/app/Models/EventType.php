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
     * @return HasMany<Event, self>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, Event::TYPE, self::SLUG);
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

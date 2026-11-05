<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Discord\Models;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Discord role or user an organization's event announcements mention.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $mention
 */
class DiscordOrganizationMention extends Model
{
    public const ORGANIZATION_ID = 'organization_id';

    public const MENTION = 'mention';

    protected $table = 'tt_discord_organization_mentions';

    protected $fillable = [self::ORGANIZATION_ID, self::MENTION];

    protected $casts = [self::ORGANIZATION_ID => 'int'];

    public static function forOrganization(int $organization_id): ?string
    {
        $mention = static::query()
            ->where(self::ORGANIZATION_ID, $organization_id)
            ->value(self::MENTION);

        return $mention === null || $mention === '' ? null : $mention;
    }

    /**
     * Save the mention for an organization. An empty mention removes it.
     */
    public static function store(int $organization_id, ?string $mention): void
    {
        $mention = trim((string) $mention);

        if ($mention === '')
        {
            static::query()->where(self::ORGANIZATION_ID, $organization_id)->delete();

            return;
        }

        static::query()->updateOrCreate(
            [self::ORGANIZATION_ID => $organization_id],
            [self::MENTION => $mention],
        );
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

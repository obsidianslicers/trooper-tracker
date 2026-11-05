<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\GoogleSheets\Models;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Google Sheet a club's synchronizer reads its roster from.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $sheet_id
 */
class OrganizationSheet extends Model
{
    public const ORGANIZATION_ID = 'organization_id';

    public const SHEET_ID = 'sheet_id';

    protected $table = 'tt_google_sheets_organization_sheets';

    protected $fillable = [self::ORGANIZATION_ID, self::SHEET_ID];

    protected $casts = [self::ORGANIZATION_ID => 'int'];

    public static function forOrganization(int $organization_id): ?string
    {
        $sheet_id = static::query()
            ->where(self::ORGANIZATION_ID, $organization_id)
            ->value(self::SHEET_ID);

        return $sheet_id === null || $sheet_id === '' ? null : $sheet_id;
    }

    /**
     * Save an organization's sheet. An empty ID removes it.
     */
    public static function store(int $organization_id, ?string $sheet_id): void
    {
        $sheet_id = trim((string) $sheet_id);

        if ($sheet_id === '')
        {
            static::query()->where(self::ORGANIZATION_ID, $organization_id)->delete();

            return;
        }

        static::query()->updateOrCreate(
            [self::ORGANIZATION_ID => $organization_id],
            [self::SHEET_ID => $sheet_id],
        );
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

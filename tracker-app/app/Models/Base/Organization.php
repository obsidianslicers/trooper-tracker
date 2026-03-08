<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Award;
use App\Models\Costume;
use App\Models\Event;
use App\Models\Notice;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Organization
 * 
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $type
 * @property int $depth
 * @property int $sequence
 * @property string $node_path
 * @property string|null $identifier_display
 * @property string|null $identifier_validation
 * @property string|null $image_path_lg
 * @property string|null $image_path_sm
 * @property string|null $service_class
 * @property string|null $sync_sheet_id
 * @property string|null $discord_mention
 * @property int|null $related_forum
 * @property int|null $related_forum_archive
 * @property Carbon|null $synchronized_at
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property \App\Models\Organization|null $organization
 * @property Collection|Award[] $awards
 * @property Collection|Event[] $events
 * @property Collection|Notice[] $notices
 * @property Collection|Costume[] $costumes
 * @property Collection|\App\Models\Organization[] $organizations
 * @property Collection|TrooperAssignment[] $trooper_assignments
 * @property Collection|Trooper[] $troopers
 *
 * @package App\Models\Base
 */
class Organization extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const PARENT_ID = 'parent_id';
    const NAME = 'name';
    const TYPE = 'type';
    const DEPTH = 'depth';
    const SEQUENCE = 'sequence';
    const NODE_PATH = 'node_path';
    const IDENTIFIER_DISPLAY = 'identifier_display';
    const IDENTIFIER_VALIDATION = 'identifier_validation';
    const IMAGE_PATH_LG = 'image_path_lg';
    const IMAGE_PATH_SM = 'image_path_sm';
    const SERVICE_CLASS = 'service_class';
    const SYNC_SHEET_ID = 'sync_sheet_id';
    const DISCORD_MENTION = 'discord_mention';
    const RELATED_FORUM = 'related_forum';
    const RELATED_FORUM_ARCHIVE = 'related_forum_archive';
    const SYNCHRONIZED_AT = 'synchronized_at';
    const DESCRIPTION = 'description';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_organizations';

    protected $casts = [
        self::ID => 'int',
        self::PARENT_ID => 'int',
        self::DEPTH => 'int',
        self::SEQUENCE => 'int',
        self::RELATED_FORUM => 'int',
        self::RELATED_FORUM_ARCHIVE => 'int',
        self::SYNCHRONIZED_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::PARENT_ID,
        self::NAME,
        self::TYPE,
        self::DEPTH,
        self::SEQUENCE,
        self::NODE_PATH,
        self::IDENTIFIER_DISPLAY,
        self::IDENTIFIER_VALIDATION,
        self::IMAGE_PATH_LG,
        self::IMAGE_PATH_SM,
        self::SERVICE_CLASS,
        self::SYNC_SHEET_ID,
        self::DISCORD_MENTION,
        self::RELATED_FORUM,
        self::RELATED_FORUM_ARCHIVE,
        self::SYNCHRONIZED_AT,
        self::DESCRIPTION
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, \App\Models\Organization::PARENT_ID);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(Award::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, Event::PRIMARY_ORGANIZATION_ID);
    }

    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class);
    }

    public function costumes(): BelongsToMany
    {
        return $this->belongsToMany(Costume::class, 'tt_organization_costumes')
                    ->withPivot(OrganizationCostume::ID, OrganizationCostume::PREFIX, OrganizationCostume::SYNCHRONIZED_AT, OrganizationCostume::DELETED_AT, OrganizationCostume::CREATED_ID, OrganizationCostume::UPDATED_ID, OrganizationCostume::DELETED_ID)
                    ->withTimestamps();
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(\App\Models\Organization::class, \App\Models\Organization::PARENT_ID);
    }

    public function trooper_assignments(): HasMany
    {
        return $this->hasMany(TrooperAssignment::class);
    }

    public function troopers(): BelongsToMany
    {
        return $this->belongsToMany(Trooper::class, 'tt_trooper_organizations')
                    ->withPivot(TrooperOrganization::ID, TrooperOrganization::IDENTIFIER, TrooperOrganization::MEMBERSHIP_STATUS, TrooperOrganization::JOIN_DATE, TrooperOrganization::SYNCHRONIZED_AT, TrooperOrganization::DELETED_AT, TrooperOrganization::CREATED_ID, TrooperOrganization::UPDATED_ID, TrooperOrganization::DELETED_ID)
                    ->withTimestamps();
    }
}

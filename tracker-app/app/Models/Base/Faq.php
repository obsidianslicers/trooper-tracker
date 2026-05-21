<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Faq
 *
 * @property int $id
 * @property int $section_id
 * @property string $title
 * @property string|null $description
 * @property string|null $video_url
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 *
 * @property FaqSection $section
 *
 * @package App\Models\Base
 */
class Faq extends Model
{
    use SoftDeletes;

    const ID          = 'id';
    const SECTION_ID  = 'section_id';
    const TITLE       = 'title';
    const DESCRIPTION = 'description';
    const VIDEO_URL   = 'video_url';
    const SORT_ORDER  = 'sort_order';
    const CREATED_AT  = 'created_at';
    const UPDATED_AT  = 'updated_at';
    const DELETED_AT  = 'deleted_at';
    const CREATED_ID  = 'created_id';
    const UPDATED_ID  = 'updated_id';
    const DELETED_ID  = 'deleted_id';

    protected $table = 'tt_faq';

    protected $casts = [
        self::ID         => 'int',
        self::SECTION_ID => 'int',
        self::SORT_ORDER => 'int',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int',
    ];

    protected $fillable = [
        self::SECTION_ID,
        self::TITLE,
        self::DESCRIPTION,
        self::VIDEO_URL,
        self::SORT_ORDER,
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(FaqSection::class, self::SECTION_ID);
    }
}

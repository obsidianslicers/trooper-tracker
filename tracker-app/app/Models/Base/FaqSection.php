<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Faq;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FaqSection
 *
 * @property int $id
 * @property string $label
 * @property string $icon
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 *
 * @property Collection|Faq[] $faqs
 *
 * @package App\Models\Base
 */
class FaqSection extends Model
{
    use SoftDeletes;

    const ID         = 'id';
    const LABEL      = 'label';
    const ICON       = 'icon';
    const SORT_ORDER = 'sort_order';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';

    protected $table = 'tt_faq_sections';

    protected $casts = [
        self::ID         => 'int',
        self::SORT_ORDER => 'int',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int',
    ];

    protected $fillable = [
        self::LABEL,
        self::ICON,
        self::SORT_ORDER,
    ];

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'section_id');
    }
}

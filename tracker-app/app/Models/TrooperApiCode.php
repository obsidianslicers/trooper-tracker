<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores mobile API authentication keys for troopers.
 *
 * @property int $id
 * @property int $trooper_id
 * @property string $api_code
 */
class TrooperApiCode extends Model
{
    use HasFactory;

    const ID         = 'id';
    const TROOPER_ID = 'trooper_id';
    const API_CODE   = 'api_code';

    protected $table = 'tt_trooper_api_codes';

    protected $fillable = [
        self::TROOPER_ID,
        self::API_CODE,
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}

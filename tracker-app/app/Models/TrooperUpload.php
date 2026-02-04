<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrooperUpload extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tt_trooper_uploads';

    protected $fillable = [
        'organization_id',
        'identifier',
        'prefix',
        'costume_name',
        'small_image_url',
        'large_image_url',
        'bucket_off_url',
        'created_id',
        'updated_id',
        'deleted_id',
    ];

    protected $casts = [
        'organization_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
}

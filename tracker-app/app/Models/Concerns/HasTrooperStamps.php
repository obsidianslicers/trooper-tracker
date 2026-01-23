<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Trooper;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Provides automatic trooper stamping for model lifecycle events.
 *
 * This trait automatically records which authenticated trooper created, updated,
 * or deleted a model by setting created_id, updated_id, and deleted_id fields.
 * It also provides relationships to access the associated Trooper models.
 */
trait HasTrooperStamps
{
    /**
     * Boot the HasTrooperStamps trait for a model.
     *
     * Registers event listeners for creating, updating, restoring, and deleting
     * events to automatically set trooper ID fields based on the authenticated user.
     * For soft-deleting models, sets deleted_id on deletion and clears it on restore.
     *
     * @return void
     */
    public static function bootHasTrooperStamps(): void
    {
        static::creating(function ($model)
        {
            if (Auth::check())
            {
                $model->created_id = Auth::id();
                $model->updated_id = Auth::id();
            }
        });

        static::updating(function ($model)
        {
            if (Auth::check())
            {
                $model->updated_id = Auth::id();
            }
            else
            {
                $model->updated_id = null;
            }
        });

        static::restoring(function ($model)
        {
            $model->deleted_id = null;
        });

        static::deleting(function ($model)
        {
            if (static::usingSoftDeletes())
            {
                if (Auth::check())
                {
                    $model->deleted_id = Auth::id();
                }
                else
                {
                    $model->deleted_id = null;
                }
            }
        });
    }

    /**
     * Get the trooper that created the model.
     *
     * @return BelongsTo<Trooper, self>
     */
    public function created_by(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, 'created_id', 'id');
    }

    /**
     * Get the trooper that last updated the model.
     *
     * @return BelongsTo<Trooper, self>
     */
    public function updated_by(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, 'updated_id', 'id');
    }

    /**
     * Get the trooper that soft-deleted the model.
     *
     * @return BelongsTo<Trooper, self>
     */
    public function deleted_by(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, 'deleted_id', 'id');
    }

    /**
     * Check if the model uses the SoftDeletes trait.
     *
     * @return bool True if the model uses SoftDeletes, false otherwise.
     */
    private static function usingSoftDeletes(): bool
    {
        return $usingSoftDeletes = in_array(
            'Illuminate\Database\Eloquent\SoftDeletes',
            class_uses_recursive(get_called_class())
        );
    }
}

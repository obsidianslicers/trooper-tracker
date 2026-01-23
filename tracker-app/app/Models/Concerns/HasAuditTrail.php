<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\ModelChange;

/**
 * Provides audit trail functionality for models.
 *
 * This trait automatically tracks changes to specified model attributes by creating
 * ModelChange records whenever audited fields are updated. Models using this trait
 * must implement the audits() method to define which fields should be tracked.
 */
trait HasAuditTrail
{
    /**
     * Define which model attributes should be audited for changes.
     *
     * Models using this trait should override this method to return an array
     * of attribute names that should be tracked in the audit trail.
     *
     * @return array<int, string> Array of attribute names to audit.
     */
    protected function audits(): array
    {
        return [];
    }

    /**
     * Boot the HasAuditTrail trait for a model.
     *
     * Registers an 'updating' event listener that automatically creates ModelChange
     * records for any audited fields that have been modified. Only fields returned
     * by the audits() method are tracked. Records include the authenticated trooper
     * ID, old value, and new value.
     *
     * @return void
     */
    public static function bootHasAuditTrail(): void
    {
        static::updating(function ($model)
        {
            $audits = $model->audits();

            if (!empty($audits))
            {
                foreach ($model->getDirty() as $field => $new_value)
                {
                    if (!in_array($field, $audits, true))
                    {
                        continue;
                    }

                    $old_value = $model->getOriginal($field);

                    ModelChange::create([
                        ModelChange::AUDITABLE_TYPE => $model::class,
                        ModelChange::AUDITABLE_ID => $model->id,
                        ModelChange::TROOPER_ID => auth()->id(),
                        ModelChange::FIELD_NAME => $field,
                        ModelChange::OLD_VALUE => $old_value,
                        ModelChange::NEW_VALUE => $new_value,
                    ]);
                }
            }
        });
    }
}

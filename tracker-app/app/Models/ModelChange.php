<?php

namespace App\Models;

use App\Models\Base\ModelChange as BaseModelChange;
use App\Models\Scopes\HasModelChangeScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents an audit trail record of changes to model attributes.
 *
 * This model tracks changes to auditable fields on other models, recording
 * the old and new values, which trooper made the change, and when it occurred.
 * Uses polymorphic relationships to link to any auditable model.
 */
class ModelChange extends BaseModelChange
{
    use HasModelChangeScopes;
    use HasFactory;

    /**
     * Get the auditable model that was changed.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo The polymorphic relationship to the auditable model.
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Get a human-readable label for the auditable model.
     *
     * Uses the model's getAuditLabel() method if available, otherwise
     * returns a default label with the class basename and ID.
     *
     * @return string The label representing the auditable model.
     */
    public function getAuditableLabelAttribute(): string
    {
        return method_exists($this->auditable, 'getAuditLabel')
            ? $this->auditable->getAuditLabel()
            : class_basename($this->auditable_type) . ' #' . $this->auditable_id;
    }

}

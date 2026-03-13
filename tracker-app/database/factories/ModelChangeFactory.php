<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AwardFrequency;
use App\Models\Award;
use App\Models\EventTrooper;
use App\Models\ModelChange;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Database\Factories\Base\ModelChangeFactory as BaseModelChangeFactory;

class ModelChangeFactory extends BaseModelChangeFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
        ]);
    }

    /**
     * Set the auditable model to a Trooper.
     */
    public function forTrooper(Trooper $trooper): static
    {
        return $this->state([
            ModelChange::AUDITABLE_TYPE => Trooper::class,
            ModelChange::AUDITABLE_ID => $trooper->id,
        ]);
    }

    /**
     * Set the auditable model to an EventTrooper.
     */
    public function forEventTrooper(EventTrooper $event_trooper): static
    {
        return $this->state([
            ModelChange::AUDITABLE_TYPE => EventTrooper::class,
            ModelChange::AUDITABLE_ID => $event_trooper->id,
        ]);
    }

    /**
     * Set the created_at timestamp.
     */
    public function createdAt(Carbon $date): static
    {
        return $this->state([
            ModelChange::CREATED_AT => $date,
        ]);
    }

    /**
     * Set the field name.
     */
    public function withFieldName(string $field_name): static
    {
        return $this->state([
            ModelChange::FIELD_NAME => $field_name,
        ]);
    }

    /**
     * Set the old value.
     */
    public function withOldValue(?string $old_value): static
    {
        return $this->state([
            ModelChange::OLD_VALUE => $old_value,
        ]);
    }

    /**
     * Set the new value.
     */
    public function withNewValue(?string $new_value): static
    {
        return $this->state([
            ModelChange::NEW_VALUE => $new_value,
        ]);
    }
}
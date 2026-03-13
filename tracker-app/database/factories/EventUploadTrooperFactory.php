<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EventUpload;
use App\Models\EventUploadTrooper;
use App\Models\Trooper;
use Database\Factories\Base\EventUploadTrooperFactory as BaseEventUploadTrooperFactory;

class EventUploadTrooperFactory extends BaseEventUploadTrooperFactory
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

    public function forEventUpload(EventUpload $event_upload): static
    {
        return $this->state(fn(array $attributes): array => [
            EventUploadTrooper::EVENT_UPLOAD_ID => $event_upload->{EventUpload::ID},
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            EventUploadTrooper::TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }
}

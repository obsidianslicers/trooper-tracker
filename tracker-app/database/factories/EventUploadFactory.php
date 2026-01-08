<?php

namespace Database\Factories;

use App\Models\EventUpload;
use Database\Factories\Base\EventUploadFactory as BaseEventUploadFactory;

class EventUploadFactory extends BaseEventUploadFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            EventUpload::IMAGE_PATH_SM => $this->faker->imageUrl(400, 300, 'cats', true, 'Faker'),
            EventUpload::IMAGE_PATH_LG => $this->faker->imageUrl(400, 300, 'cats', true, 'Faker'),
        ]);
    }
}

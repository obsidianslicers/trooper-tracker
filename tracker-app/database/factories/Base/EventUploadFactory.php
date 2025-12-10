<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\EventUpload;

class EventUploadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            EventUpload::EVENT_ID => \App\Models\Event::factory(),
            EventUpload::TROOPER_ID => \App\Models\Trooper::factory(),
            EventUpload::FILENAME => $this->faker->word(),
        ];
    }
}

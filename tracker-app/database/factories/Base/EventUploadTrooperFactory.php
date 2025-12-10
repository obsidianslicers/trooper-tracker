<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\EventUploadTrooper;

class EventUploadTrooperFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            EventUploadTrooper::EVENT_UPLOAD_ID => \App\Models\EventUpload::factory(),
            EventUploadTrooper::TROOPER_ID => \App\Models\Trooper::factory(),
        ];
    }
}

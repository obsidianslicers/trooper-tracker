<?php

namespace Database\Factories;

use App\Models\TrooperUpload;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrooperUploadFactory extends Factory
{
    protected $model = TrooperUpload::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'identifier' => 'TK-' . $this->faker->numerify('#####'),
            'prefix' => $this->faker->optional()->word(),
            'costume_name' => $this->faker->word(),
            'small_image_url' => $this->faker->optional()->imageUrl(200,200),
            'large_image_url' => $this->faker->optional()->imageUrl(800,800),
            'bucket_off_url' => $this->faker->optional()->url(),
        ];
    }
}

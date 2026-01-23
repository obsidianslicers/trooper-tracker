<?php

namespace Database\Factories;

use App\Enums\AwardFrequency;
use App\Models\Award;
use App\Models\Organization;
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
}
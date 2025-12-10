<?php

namespace Database\Factories;

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
}

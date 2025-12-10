<?php

namespace Database\Factories;

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
        ]);
    }
}

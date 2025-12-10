<?php

namespace Database\Factories;

use Database\Factories\Base\NoticeTrooperFactory as BaseNoticeTrooperFactory;

class NoticeTrooperFactory extends BaseNoticeTrooperFactory
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

<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\NoticeTrooper;

class NoticeTrooperFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            NoticeTrooper::NOTICE_ID => \App\Models\Notice::factory(),
            NoticeTrooper::TROOPER_ID => \App\Models\Trooper::factory(),
            NoticeTrooper::IS_READ => $this->faker->randomNumber(1),
        ];
    }
}

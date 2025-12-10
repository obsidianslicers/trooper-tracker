<?php

namespace Database\Factories;

use App\Models\Setting;
use Database\Factories\Base\SettingFactory as BaseSettingFactory;

class SettingFactory extends BaseSettingFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            Setting::KEY => 'k' . uniqid(),
        ]);
    }
}

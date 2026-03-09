<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Notice;
use App\Models\NoticeTrooper;
use App\Models\Trooper;
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

    public function forNotice(Notice $notice): static
    {
        return $this->state(fn(array $attributes): array => [
            NoticeTrooper::NOTICE_ID => $notice->{Notice::ID},
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            NoticeTrooper::TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }

    public function asRead(): static
    {
        return $this->state(fn(array $attributes): array => [
            NoticeTrooper::IS_READ => true,
        ]);
    }

    public function asUnread(): static
    {
        return $this->state(fn(array $attributes): array => [
            NoticeTrooper::IS_READ => false,
        ]);
    }
}

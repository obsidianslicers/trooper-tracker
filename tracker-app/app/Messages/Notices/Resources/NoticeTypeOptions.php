<?php

declare(strict_types=1);

namespace App\Messages\Notices\Resources;

use App\Enums\NoticeType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The notice types on the Command Staff notice form, each with the banner it shows.
 *
 * @property array<int, NoticeType> $resource
 */
class NoticeTypeOptions extends JsonResource
{
    public function __construct()
    {
        parent::__construct(NoticeType::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function toArray(Request $request): array
    {
        return array_map(fn (NoticeType $type) => [
            'value' => $type->value,
            'label' => $this->banner($type),
        ], $this->resource);
    }

    private function banner(NoticeType $type): string
    {
        return match ($type)
        {
            NoticeType::INFO => 'ℹ️ '.__('admin.NOW HEAR THIS!'),
            NoticeType::SUCCESS => '✔️ '.__('admin.MISSION ACCOMPLISHED!'),
            NoticeType::WARNING => '⚠️ '.__('admin.ATTENTION TROOPERS!'),
            NoticeType::DANGER => '❌ '.__('admin.BATTLE STATIONS!'),
        };
    }
}

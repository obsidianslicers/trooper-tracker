<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\FlashType;

/**
 * The toast an action flashed (FlashType) for the page it goes back to.
 */
trait ReadsToasts
{
    protected function toast(): ?string
    {
        $type = $this->toastType();

        return $type === null ? null : (string) session($type);
    }

    protected function toastType(): ?string
    {
        foreach (FlashType::cases() as $type)
        {
            if (session()->has($type->value))
            {
                return $type->value;
            }
        }

        return null;
    }
}

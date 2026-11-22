<?php

declare(strict_types=1);

namespace Tests\Concerns;

/**
 * The toast an event page action left for the page it goes back to (FlashType::toast()).
 */
trait ReadsToasts
{
    protected function toast(): ?string
    {
        $toasts = (array) session('toast', []);

        return $toasts === [] ? null : (string) reset($toasts);
    }

    protected function toastType(): ?string
    {
        $toasts = (array) session('toast', []);

        return $toasts === [] ? null : (string) array_key_first($toasts);
    }
}

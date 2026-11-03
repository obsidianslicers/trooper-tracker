<?php

declare(strict_types=1);

namespace App\Plugins;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Throwable;

/**
 * Named places in the core views where plugins can add their own markup.
 *
 * Core views render a slot with the @pluginSlot('name') Blade directive. Each
 * entry is rendered with the view's own variables plus the entry's data.
 *
 * Svelte pages cannot render Blade, so the slots listed in SVELTE_SLOTS are
 * rendered to HTML on the server and shared with every Inertia page as the
 * "plugin_slots" prop, which the <PluginSlot name="..."> component displays.
 */
class PluginSlots
{
    public const SVELTE_SLOTS = [
        'account.profile.sections',
        'account.pending.sections',
        'admin.troopers.approvals.sections',
    ];

    /**
     * @var array<string, array<int, array{
     *     view: string, data: array|Closure, when: ?Closure, order: int
     * }>>
     */
    private array $entries = [];

    public function add(
        string $slot,
        string $view,
        array|Closure $data = [],
        ?Closure $when = null,
        int $order = 100,
    ): void {
        $this->entries[$slot][] = compact('view', 'data', 'when', 'order');
    }

    public function has(string $slot): bool
    {
        return !empty($this->entries[$slot]);
    }

    /**
     * @param  array<string, mixed>  $context  The variables of the view rendering the slot.
     */
    public function render(string $slot, array $context = []): string
    {
        $context = array_filter(
            $context,
            fn (string $key): bool => !str_starts_with($key, '__') && $key !== 'app',
            ARRAY_FILTER_USE_KEY,
        );

        $entries = collect($this->entries[$slot] ?? [])->sortBy('order');

        return $entries
            ->map(fn (array $entry): string => $this->renderEntry($slot, $entry, $context))
            ->implode('');
    }

    /**
     * Render several slots at once, leaving out the empty ones.
     *
     * @param  array<int, string>  $slots
     * @param  array<string, mixed>  $context
     * @return array<string, string> Rendered HTML keyed by slot name.
     */
    public function renderMany(array $slots, array $context = []): array
    {
        $rendered = [];

        foreach ($slots as $slot)
        {
            $html = $this->has($slot) ? $this->render($slot, $context) : '';

            if ($html !== '')
            {
                $rendered[$slot] = $html;
            }
        }

        return $rendered;
    }

    private function renderEntry(string $slot, array $entry, array $context): string
    {
        try
        {
            if ($entry['when'] !== null && !($entry['when'])($context))
            {
                return '';
            }

            $data = $entry['data'] instanceof Closure ? ($entry['data'])($context) : $entry['data'];

            return View::make($entry['view'], [...$context, ...$data])->render();
        }
        catch (Throwable $e)
        {
            Log::error(
                "Plugin view [{$entry['view']}] in slot [{$slot}] failed: {$e->getMessage()}",
                ['exception' => $e],
            );

            report($e);

            return '';
        }
    }
}

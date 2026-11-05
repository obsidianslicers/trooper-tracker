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
    /**
     * View factory properties that a failed render resets.
     */
    private const VIEW_STATE = [
        'renderCount', 'renderedOnce', 'sections', 'sectionStack', 'pushes', 'prepends',
        'pushStack', 'componentStack', 'componentData', 'currentComponentData', 'slots',
        'slotStack', 'fragments', 'fragmentStack',
    ];

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
        $view_state = $this->viewState();

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
            $this->restoreViewState($view_state);

            Log::error(
                "Plugin view [{$entry['view']}] in slot [{$slot}] failed: {$e->getMessage()}",
                ['exception' => $e],
            );

            report($e);

            return '';
        }
    }

    /**
     * A view that throws flushes the view factory's state, which would break the
     * page still rendering around the slot (its open sections and components).
     * These save and put back that state, so a failing entry only drops itself.
     *
     * @return array<string, mixed>
     */
    private function viewState(): array
    {
        $keys = array_flip(self::VIEW_STATE);

        return (fn (): array => array_intersect_key(get_object_vars($this), $keys))
            ->call(View::getFacadeRoot());
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function restoreViewState(array $state): void
    {
        (function () use ($state): void
        {
            foreach ($state as $property => $value)
            {
                $this->{$property} = $value;
            }
        })->call(View::getFacadeRoot());
    }
}

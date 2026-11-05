<?php

declare(strict_types=1);

namespace App\Plugins;

/**
 * Notification categories that enabled plugins have added. Each appears on the
 * account page's notification preferences next to core's own.
 */
final class PluginNotifications
{
    /**
     * @var array<string, array{label: string, for_command_staff: bool, administrator_only: bool}>
     */
    private array $categories = [];

    public function add(
        string $value,
        string $label,
        bool $for_command_staff = false,
        bool $administrator_only = false,
    ): void {
        $this->categories[$value] = [
            'label' => $label,
            'for_command_staff' => $for_command_staff || $administrator_only,
            'administrator_only' => $administrator_only,
        ];
    }

    /**
     * @return array<string, string> Value => label
     */
    public function trooperCategories(): array
    {
        return $this->labels(fn (array $category): bool => !$category['for_command_staff']);
    }

    /**
     * @return array<string, string> Value => label
     */
    public function commandStaffCategories(bool $include_administrator_only = true): array
    {
        return $this->labels(fn (array $category): bool => $category['for_command_staff']
            && ($include_administrator_only || !$category['administrator_only']));
    }

    /**
     * @return array<int, string>
     */
    public function values(): array
    {
        return array_keys($this->categories);
    }

    /**
     * @param  callable(array{
     *     label: string,
     *     for_command_staff: bool,
     *     administrator_only: bool,
     * }): bool  $filter
     * @return array<string, string>
     */
    private function labels(callable $filter): array
    {
        return array_map(
            fn (array $category): string => $category['label'],
            array_filter($this->categories, $filter),
        );
    }
}

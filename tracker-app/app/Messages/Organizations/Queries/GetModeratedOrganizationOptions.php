<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Queries;

use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Select options for the organizations a trooper moderates, in tree order and indented by level.
 * Administrators can also pick "Everyone" (no organization) when `with_everyone` is set.
 *
 * @method static array<int, array{value: int|null, label: string}> call(Trooper $trooper, bool $with_everyone = false)
 */
final class GetModeratedOrganizationOptions extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly bool $with_everyone = false,
    ) {}

    /**
     * @return array<int, array{value: int|null, label: string}>
     */
    public function handle(): array
    {
        $options = [];

        if ($this->with_everyone && $this->trooper->is_administrator)
        {
            $options[] = ['value' => null, 'label' => __('admin.Everyone')];
        }

        $organizations = Organization::moderatedBy($this->trooper)
            ->orderBy(Organization::SEQUENCE)
            ->get();

        foreach ($organizations as $organization)
        {
            $indent = str_repeat('— ', max(0, $organization->depth - 1));

            $options[] = ['value' => $organization->id, 'label' => $indent.$organization->name];
        }

        return $options;
    }
}

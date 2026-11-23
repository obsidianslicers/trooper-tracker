<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A select's choices for the Command Staff event pages, from value => label pairs: the status
 * filter on the events list, the status pickers on Create Event and the event's tabs, and the
 * roster's status and costume pickers.
 *
 * @property array<string|int, string> $resource
 */
class ChoiceOptions extends JsonResource
{
    /**
     * @param  array<string|int, string>  $choices  value => label
     */
    public function __construct(array $choices)
    {
        parent::__construct($choices);
    }

    /**
     * @return array<int, array{value: string|int, label: string}>
     */
    public function toArray(Request $request): array
    {
        $options = [];

        foreach ($this->resource as $value => $label)
        {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Events;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class RemoveEventTrooperRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        if ($event === null)
        {
            throw new AuthorizationException('Event not found or unauthorized.');
        }

        return $this->user()->can('update', $event);
    }

    public function rules(): array
    {
        return [];
    }
}

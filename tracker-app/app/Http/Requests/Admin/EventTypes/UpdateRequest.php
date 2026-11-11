<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\EventTypes;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    use EventTypeRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('event_type'));
    }

    public function rules(): array
    {
        return $this->eventTypeRules($this->route('event_type'));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\EventTypes\Commands;

use App\Http\Requests\Admin\EventTypes\EventTypeRules;
use App\Models\EventType;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventTypeRequest extends FormRequest
{
    use EventTypeRules;

    public function authorize(): bool
    {
        return $this->user()->can('create', EventType::class);
    }

    public function rules(): array
    {
        return $this->eventTypeRules();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Models\EventTrooper;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

class RejectTroopCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event_trooper = $this->route('event_trooper');
        $organization = $this->route('organization');

        return $event_trooper instanceof EventTrooper
            && $organization instanceof Organization
            && in_array($organization->id, $event_trooper->verifiableOrganizationIdsFor($this->user()), true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}

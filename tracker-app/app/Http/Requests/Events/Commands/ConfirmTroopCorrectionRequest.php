<?php

declare(strict_types=1);

namespace App\Http\Requests\Events\Commands;

use App\Models\TroopCorrection;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Only the event coordinator a request for troop credit is waiting for may answer it.
 */
class ConfirmTroopCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $troop_correction = $this->route('troop_correction');

        return $troop_correction instanceof TroopCorrection
            && $troop_correction->isAwaitingCoordinatorFrom($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}

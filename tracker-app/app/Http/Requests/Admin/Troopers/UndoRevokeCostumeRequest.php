<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Models\TrooperCostume;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UndoRevokeCostumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trooper_costume = $this->route('trooper_costume');

        return $trooper_costume instanceof TrooperCostume
            && $trooper_costume->isReviewableBy($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'trooper_costume' => [
                function (string $attribute, mixed $value, Closure $fail): void
                {
                    if (!$value->isRevoked())
                    {
                        $fail('This costume has not been revoked.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return array_merge(parent::validationData(), [
            'trooper_costume' => $this->route('trooper_costume'),
        ]);
    }
}

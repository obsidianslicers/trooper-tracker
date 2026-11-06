<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Models\TrooperCostume;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ApproveCostumeRequest extends FormRequest
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
                    if (!$value->isPending())
                    {
                        $fail('This costume has already been reviewed.');
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

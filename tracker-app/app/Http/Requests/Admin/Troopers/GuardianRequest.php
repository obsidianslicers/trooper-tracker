<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Throwable;

class GuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trooper = $this->route('trooper');

        if ($trooper === null)
        {
            throw new AuthorizationException('Trooper not found or unauthorized.');
        }

        return $this->user()->can('update', $trooper);
    }

    public function rules(): array
    {
        $adult_cutoff = now()->subYears(18)->toDateString();

        return [
            Trooper::DATE_OF_BIRTH => ['nullable', 'date'],
            'guardian_email' => [
                Rule::requiredIf(fn (): bool => $this->requiresGuardianEmail()),
                'nullable',
                'email',
                'max:256',
                Rule::exists(Trooper::class, Trooper::EMAIL)
                    ->where(function (Builder $query) use ($adult_cutoff): void {
                        $query->whereNull(Trooper::DATE_OF_BIRTH)
                            ->orWhereDate(Trooper::DATE_OF_BIRTH, '<=', $adult_cutoff);
                    }),
            ],
        ];
    }

    private function requiresGuardianEmail(): bool
    {
        $date_of_birth = $this->submittedDateOfBirth();

        if ($date_of_birth === null)
        {
            return false;
        }

        return $date_of_birth->toDateString() > now()->subYears(18)->toDateString();
    }

    private function submittedDateOfBirth(): ?Carbon
    {
        $date_of_birth = $this->input(Trooper::DATE_OF_BIRTH);

        if (!is_string($date_of_birth) || trim($date_of_birth) === '')
        {
            return null;
        }

        try
        {
            return Carbon::parse($date_of_birth)->startOfDay();
        }
        catch (Throwable)
        {
            return null;
        }
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('guardian_email') === '')
        {
            $this->merge(['guardian_email' => null]);
        }
    }
}

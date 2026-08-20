<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Rules\Auth\UniqueOrganizationIdentifierRule;
use App\Rules\Troopers\MemberOrganizationRule;
use App\Rules\Troopers\VisitorOrganizationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTrooperOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Trooper $trooper */
        $trooper = $this->user();

        $identifier_rules = ['nullable', 'string', 'max:64'];

        $organization = Organization::find($this->integer('organization_id'));

        if ($organization !== null)
        {
            $primary_organization = $organization->getPrimaryClub();

            if (!empty($primary_organization->identifier_validation))
            {
                $organization_rules = explode('|', $primary_organization->identifier_validation);

                $identifier_rules = array_merge(
                    ['nullable'],
                    $organization_rules,
                    [new UniqueOrganizationIdentifierRule($primary_organization, $trooper)]
                );
            }
        }

        return [
            TrooperRequest::ORGANIZATION_ID => [
                'required',
                'integer',
                Rule::exists(Organization::class, Organization::ID),
                Rule::when(
                    $trooper->is_visitor,
                    [new VisitorOrganizationRule($trooper)],
                    [new MemberOrganizationRule($trooper)],
                ),
            ],
            TrooperRequest::IDENTIFIER => $identifier_rules,
        ];
    }
}

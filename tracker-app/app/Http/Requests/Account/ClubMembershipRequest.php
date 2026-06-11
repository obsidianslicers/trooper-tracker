<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Models\Organization;
use App\Models\Trooper;
use App\Rules\Auth\UniqueOrganizationIdentifierRule;
use App\Rules\Troopers\VisitorOrganizationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClubMembershipRequest extends FormRequest
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
            $primary_club = $organization->getPrimaryClub();

            if (!empty($primary_club->{Organization::IDENTIFIER_VALIDATION}))
            {
                $base_rules = explode('|', $primary_club->{Organization::IDENTIFIER_VALIDATION});
                $identifier_rules = array_merge(
                    ['nullable'],
                    $base_rules,
                    [new UniqueOrganizationIdentifierRule($primary_club, $trooper)]
                );
            }
        }

        return [
            'organization_id' => [
                'required',
                'integer',
                Rule::exists('tt_organizations', 'id'),
                new VisitorOrganizationRule($trooper),
            ],
            'identifier' => $identifier_rules,
        ];
    }
}

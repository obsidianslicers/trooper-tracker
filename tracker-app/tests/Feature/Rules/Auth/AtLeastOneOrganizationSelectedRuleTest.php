<?php

declare(strict_types=1);

namespace Tests\Feature\Rules\Auth;

use App\Rules\Auth\AtLeastOneOrganizationSelectedRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AtLeastOneOrganizationSelectedRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_when_at_least_one_organization_is_selected(): void
    {
        $validator = Validator::make([
            'organizations' => [
                ['selected' => false],
                ['selected' => true],
            ],
        ], [
            'organizations' => [new AtLeastOneOrganizationSelectedRule],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_fails_when_value_is_not_an_array(): void
    {
        $validator = Validator::make([
            'organizations' => 'invalid',
        ], [
            'organizations' => [new AtLeastOneOrganizationSelectedRule],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Please select at least one organization.',
            $validator->errors()->first('organizations')
        );
    }

    public function test_fails_when_no_organization_is_selected(): void
    {
        $validator = Validator::make([
            'organizations' => [
                ['selected' => false],
                ['selected' => 0],
                ['selected' => null],
            ],
        ], [
            'organizations' => [new AtLeastOneOrganizationSelectedRule],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Please select at least one organization.',
            $validator->errors()->first('organizations')
        );
    }
}

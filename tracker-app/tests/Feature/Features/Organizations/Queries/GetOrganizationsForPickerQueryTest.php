<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Organizations\Queries;

use App\Features\Organizations\Queries\GetOrganizationsForPickerQuery;
use App\Models\Trooper;
use Tests\TestCase;

class GetOrganizationsForPickerQueryTest extends TestCase
{
    public function test_construct_sets_trooper_and_defaults(): void
    {
        $trooper = new Trooper();

        $subject = new GetOrganizationsForPickerQuery($trooper, []);

        $this->assertSame($trooper, $subject->trooper);
        $this->assertFalse($subject->moderated_only);
        $this->assertNull($subject->organization_id);
    }

    public function test_construct_casts_moderated_only_and_organization_id_values(): void
    {
        $trooper = new Trooper();

        $subject = new GetOrganizationsForPickerQuery($trooper, [
            'moderated_only' => '1',
            'organization_id' => '42',
        ]);

        $this->assertTrue($subject->moderated_only);
        $this->assertSame(42, $subject->organization_id);
    }
}

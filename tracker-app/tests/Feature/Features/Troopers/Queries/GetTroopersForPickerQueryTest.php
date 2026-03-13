<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTroopersForPickerQuery;
use App\Models\Filters\TrooperFilter;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Tests\TestCase;

class GetTroopersForPickerQueryTest extends TestCase
{
    public function test_construct_stores_trooper_and_filter(): void
    {
        $trooper = new Trooper();
        $filter = new TrooperFilter(new Request());

        $subject = new GetTroopersForPickerQuery($trooper, $filter, []);

        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($filter, $subject->filter);
    }

    public function test_construct_defaults_moderated_only_and_organization_id(): void
    {
        $trooper = new Trooper();
        $filter = new TrooperFilter(new Request());

        $subject = new GetTroopersForPickerQuery($trooper, $filter, []);

        $this->assertFalse($subject->moderated_only);
        $this->assertNull($subject->organization_id);
    }

    public function test_construct_casts_moderated_only_and_organization_id_values(): void
    {
        $trooper = new Trooper();
        $filter = new TrooperFilter(new Request());

        $subject = new GetTroopersForPickerQuery($trooper, $filter, [
            'moderated_only' => '1',
            'organization_id' => '99',
        ]);

        $this->assertTrue($subject->moderated_only);
        $this->assertSame(99, $subject->organization_id);
    }
}

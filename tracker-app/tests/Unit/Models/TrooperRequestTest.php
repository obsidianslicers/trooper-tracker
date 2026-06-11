<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Organization;
use App\Models\TrooperRequest;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class TrooperRequestTest extends TestCase
{
    public function test_organization_relationship_uses_organization_foreign_key(): void
    {
        $subject = new TrooperRequest();

        $result = $subject->organization();

        $this->assertInstanceOf(BelongsTo::class, $result);
        $this->assertSame(TrooperRequest::ORGANIZATION_ID, $result->getForeignKeyName());
        $this->assertInstanceOf(Organization::class, $result->getRelated());
    }

    public function test_primary_organization_relationship_uses_primary_organization_foreign_key(): void
    {
        $subject = new TrooperRequest();

        $result = $subject->primaryOrganization();

        $this->assertInstanceOf(BelongsTo::class, $result);
        $this->assertSame(TrooperRequest::PRIMARY_ORGANIZATION_ID, $result->getForeignKeyName());
        $this->assertInstanceOf(Organization::class, $result->getRelated());
    }
}

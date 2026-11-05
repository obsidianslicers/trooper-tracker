<?php

declare(strict_types=1);

namespace Tests\Fixtures\Synchronizers;

use App\Contracts\MemberLookupInterface;
use App\Contracts\MemberLookupSource;
use App\Models\Organization;
use Mockery;

/**
 * A club synchronizer that only looks up members, like a plugin might offer.
 */
class LookupOnlySynchronizer implements MemberLookupSource
{
    public static ?MemberLookupInterface $lookup = null;

    public function __construct(public readonly Organization $organization) {}

    public function lookupMember(): ?MemberLookupInterface
    {
        return self::$lookup ??= Mockery::mock(MemberLookupInterface::class);
    }
}

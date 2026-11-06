<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * A club synchronizer that can also find a member in the club's own records,
 * so sign-up can check the ID a trooper enters.
 */
interface MemberLookupSource
{
    public function lookupMember(): ?MemberLookupInterface;
}

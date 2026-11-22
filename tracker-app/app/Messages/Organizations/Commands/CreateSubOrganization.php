<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Commands;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;
use InvalidArgumentException;

/**
 * Adds a region under a club, or a unit under a region, and puts the tree back in order.
 *
 * @method static Organization call(Organization $parent, string $name)
 */
final class CreateSubOrganization extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly Organization $parent,
        private readonly string $name,
    ) {}

    public function handle(): Organization
    {
        $organization = new Organization;
        $organization->parent_id = $this->parent->id;
        $organization->name = $this->name;
        $organization->type = self::childType($this->parent)
            ?? throw new InvalidArgumentException('Cannot create a sub-organization under the specified parent type.');

        $organization->save();

        Organization::resequenceAll();

        return $organization;
    }

    /**
     * The type of organization that goes under a parent: a region under a club, a unit under
     * a region, nothing under a unit.
     */
    public static function childType(Organization $parent): ?OrganizationType
    {
        return match ($parent->type)
        {
            OrganizationType::ORGANIZATION => OrganizationType::REGION,
            OrganizationType::REGION => OrganizationType::UNIT,
            default => null,
        };
    }
}

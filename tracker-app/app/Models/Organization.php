<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationType;
use App\Models\Base\Organization as BaseOrganization;
use App\Models\Concerns\HasObserver;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasOrganizationScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents an organization within the trooping community hierarchy.
 *
 * Organizations form a hierarchical structure with three levels:
 * - Organizations (parent level)
 * - Regions (children of organizations)
 * - Units (children of regions)
 *
 * Each organization can have multiple costumes, track event participation,
 * and maintain relationships with events and troopers.
 */
class Organization extends BaseOrganization
{
    use HasObserver;
    use HasOrganizationScopes;
    use HasFactory;
    use HasTrooperStamps;

    const NODE_PATH_SEP = ':';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts()
    {
        return array_merge($this->casts, [
            self::TYPE => OrganizationType::class,
        ]);
    }

    /**
     * Alias for organization()
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Organization, Organization>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, self::PARENT_ID);
    }

    /**
     * Get all event organizations associated with this organization.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<EventOrganization>
     */
    public function event_organizations()
    {
        return $this->hasMany(EventOrganization::class);
    }

    /**
     * Get the name of the organization with indentation based on its level in the hierarchy.
     *
     * Top-level organizations have no indentation, regions have one level of indentation,
     * and units have two levels of indentation.
     *
     * @return string The indented name of the organization.
     */
    public function getIndentedNameAttribute(): string
    {
        $level = 0;
        $organization = $this;

        while ($organization->parent_id !== null)
        {
            $level++;
            $organization = $organization->parent;
        }

        return str_repeat(' - ', $level) . $this->name;
    }

    /**
     * Get the top-level organization (club) for this organization.
     *
     * Traverses up the parent hierarchy until the top-level organization is found.
     *
     * @return Organization The top-level organization.
     */
    public function getPrimaryClub(): Organization
    {
        $organization = $this;

        while ($organization->parent_id !== null)
        {
            $organization = $organization->parent;
        }

        return $organization;
    }

    /**
     * Resequence all organizations, regions, and units in hierarchical order.
     *
     * Assigns sequence numbers starting at 900, incrementing by 100 for each
     * organization, then its regions, then each region's units. This establishes
     * a consistent ordering for the organizational hierarchy.
     *
     * @return void
     */
    public static function resequenceAll()
    {
        $organizations = self::ofTypeOrganizations()->orderBy(self::NAME)->get();

        $seq = 900;

        foreach ($organizations as $organization)
        {
            $seq += 100;

            $organization->updateQuietly([self::SEQUENCE => $seq]);

            $regions = $organization->organizations()->ofTypeRegions()->orderBy(self::NAME)->get();

            foreach ($regions as $region)
            {
                $seq += 100;

                $region->updateQuietly([self::SEQUENCE => $seq]);

                $units = $region->organizations()->ofTypeUnits()->orderBy(self::NAME)->get();

                foreach ($units as $unit)
                {
                    $seq += 100;

                    $unit->updateQuietly([self::SEQUENCE => $seq]);
                }
            }
        }
    }
}

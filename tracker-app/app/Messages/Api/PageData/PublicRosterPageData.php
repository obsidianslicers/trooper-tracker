<?php

declare(strict_types=1);

namespace App\Messages\Api\PageData;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use Hyperdrive\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * A club's active members for the public roster, with its command staff apart. Kept for a day.
 *
 * Each member is their name, their identifier with its costume prefix, and a costume photo.
 *
 * @method static array call(int $organization_id)
 */
final class PublicRosterPageData extends Message
{
    public const CACHE_SECONDS = 86400;

    public function __construct(private readonly int $organization_id) {}

    public static function cacheKey(int $organization_id): string
    {
        return "tracker:public-roster:{$organization_id}";
    }

    /**
     * @return array{command_staff: list<array{name: string, identifier: string, photo: string|null}>, members: list<array{name: string, identifier: string, photo: string|null}>}
     */
    public function handle(): array
    {
        $organization = Organization::find($this->organization_id);

        if (!$organization)
        {
            return ['command_staff' => [], 'members' => []];
        }

        return Cache::remember(
            self::cacheKey($organization->id),
            self::CACHE_SECONDS,
            fn (): array => $this->roster($organization),
        );
    }

    private function roster(Organization $organization): array
    {
        $org_ids = Organization::withinNodePath($organization->node_path)->pluck(Organization::ID);

        $staff_ids = TrooperAssignment::whereIn(TrooperAssignment::ORGANIZATION_ID, $org_ids)
            ->where(TrooperAssignment::IS_MODERATOR, true)
            ->pluck(TrooperAssignment::TROOPER_ID)
            ->unique();

        $is_staff = fn (Trooper $t): bool => $staff_ids->contains($t->id)
            || $t->membership_role->hasAdministratorAccess();

        $troopers = $this->troopers($org_ids);

        return [
            'command_staff' => $troopers->filter($is_staff)->map($this->card(...))->values()->all(),
            'members' => $troopers->reject($is_staff)->map($this->card(...))->values()->all(),
        ];
    }

    private function troopers(Collection $org_ids): Collection
    {
        return Trooper::active()
            ->whereHas('trooper_assignments', fn ($q) => $q
                ->whereIn(TrooperAssignment::ORGANIZATION_ID, $org_ids)
                ->where(TrooperAssignment::IS_MEMBER, true)
            )
            ->with([
                'trooper_costumes.organization_costume',
                'organizations' => fn ($q) => $q->withPivot(TrooperOrganization::IDENTIFIER),
            ])
            ->orderBy(Trooper::DISPLAY_NAME)
            ->get();
    }

    /**
     * @return array{name: string, identifier: string, photo: string|null}
     */
    private function card(Trooper $trooper): array
    {
        $identifier = $trooper->organizations->first()?->pivot?->identifier ?? '';
        $org_id = $trooper->organizations->first()?->id;

        $photo = $trooper->trooper_costumes
            ->firstWhere(fn ($c) => !empty($c->{TrooperCostume::IMAGE_URL_SM}))
            ?->{TrooperCostume::IMAGE_URL_SM};

        return [
            'name' => $trooper->display_name,
            'identifier' => $this->displayPrefix($trooper, $org_id).$identifier,
            'photo' => $photo,
        ];
    }

    private function displayPrefix(Trooper $trooper, ?int $org_id): string
    {
        if ($trooper->display_costume_id !== null)
        {
            $prefix_costume = $trooper->trooper_costumes
                ->firstWhere(TrooperCostume::ID, $trooper->display_costume_id);
        }
        else
        {
            $prefix_costume = $trooper->trooper_costumes
                ->filter(fn ($tc) => !empty($tc->organization_costume?->prefix)
                    && $tc->organization_costume->organization_id === $org_id)
                ->sortBy(fn ($tc) => $tc->organization_costume->prefix)
                ->first();
        }

        return $prefix_costume?->organization_costume?->prefix ?? '';
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\EventUpload;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * Provides a public API for use by external developers.
 *
 * @author Matthew Drennan
 */
class PublicApiController
{
    public function __invoke(Request $request): JsonResponse|Response
    {
        if ($request->has('trooperid') || $request->has('tkid'))
        {
            return $this->trooperHistory($request);
        }

        if ($request->has('roster'))
        {
            return $this->roster($request);
        }

        if ($request->has('photos') && $request->has('amount'))
        {
            return $this->photos($request);
        }

        return response()->json([]);
    }

    private function trooperHistory(Request $request): JsonResponse
    {
        $trooper = null;

        if ($request->has('trooperid'))
        {
            $trooper = Trooper::find($request->integer('trooperid'));
        }
        elseif ($request->has('tkid') && $request->has('squad'))
        {
            $trooper_org = TrooperOrganization::where(TrooperOrganization::IDENTIFIER, $request->input('tkid'))
                ->where(TrooperOrganization::ORGANIZATION_ID, $request->integer('squad'))
                ->with('trooper')
                ->first();

            $trooper = $trooper_org?->trooper;
        }

        if (!$trooper)
        {
            return response()->json([]);
        }

        $identifier = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->value(TrooperOrganization::IDENTIFIER);

        $event_troopers = EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->whereHas('event_shift.event', fn ($q) => $q->where(Event::STATUS, EventStatus::CLOSED->value))
            ->with(['event_shift.event'])
            ->get();

        $event_array = $event_troopers
            ->map(fn ($et) => [
                'eventID' => $et->event_shift->event->id,
                'eventName' => $et->event_shift->event->name,
                'dateStart' => $et->event_shift->event->event_start,
                'dateEnd' => $et->event_shift->event->event_end,
            ])
            ->sortByDesc('dateEnd')
            ->values()
            ->all();

        $data = [[
            'trooperName' => $trooper->display_name,
            'tkid' => $identifier,
            '501forum' => null,
            'rebelforum' => null,
            'events' => $event_array,
            'troopCount' => count($event_array),
        ]];

        return response()->json($data);
    }

    private function photos(Request $request): JsonResponse|Response
    {
        $amount = $request->integer('amount');

        $uploads = EventUpload::where(EventUpload::IS_ADMINISTRATIVE, false)
            ->inRandomOrder()
            ->limit($amount)
            ->get();

        $upload_array = $uploads->map(fn ($upload) => [
            'fileName' => $upload->image_path_sm,
            'troopID' => $upload->event_id,
            'trooperID' => $upload->trooper_id,
        ])->toArray();

        if ($request->has('slideshow'))
        {
            return $this->slideshowResponse($upload_array);
        }

        // Double-wrapped intentionally: legacy API contract expects [[...items...]]
        return response()->json([$upload_array]);
    }

    private function roster(Request $request): Response
    {
        $org = Organization::find($request->integer('org', 0));

        if (!$org)
        {
            return $this->rosterResponse(collect(), collect());
        }

        $org_ids = Organization::where(Organization::NODE_PATH, 'like', $org->node_path.'%')
            ->pluck(Organization::ID);

        $troopers = Trooper::active()
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

        $staff_ids = TrooperAssignment::whereIn(TrooperAssignment::ORGANIZATION_ID, $org_ids)
            ->where(TrooperAssignment::IS_MODERATOR, true)
            ->pluck(TrooperAssignment::TROOPER_ID)
            ->unique();

        return $this->rosterResponse($troopers, $staff_ids);
    }

    private function rosterResponse(Collection $troopers, Collection $staff_ids): Response
    {
        $command_staff = $troopers->filter(fn ($t) => $staff_ids->contains($t->id) || $t->membership_role === MembershipRole::ADMINISTRATOR);
        $members = $troopers->reject(fn ($t) => $staff_ids->contains($t->id) || $t->membership_role === MembershipRole::ADMINISTRATOR);

        $html = '<style>
body{margin:0;padding:10px;font-family:Arial,sans-serif;background:#fff;color:#323f4e}
h1,h2{text-align:center;margin-bottom:16px}
h2{font-size:1em;border-bottom:1px solid #ccc;padding-bottom:6px;margin-top:20px}
.roster{display:flex;flex-wrap:wrap}
.member{width:110px;text-align:center;border:1px dashed #ccc;margin:10px;padding:8px;font-size:12px;word-break:break-word}
.member img{width:75px;height:101px;object-fit:cover;display:block;margin:0 auto 6px}
</style>';

        $html .= '<h1>Members</h1>';

        if ($command_staff->isNotEmpty())
        {
            $html .= '<h2>Leadership, Liaisons, and Unit Staff</h2><div class="roster">';
            foreach ($command_staff as $trooper)
            {
                $html .= $this->memberCard($trooper);
            }
            $html .= '</div>';
        }

        $html .= '<h2>Members</h2><div class="roster">';

        foreach ($members as $trooper)
        {
            $html .= $this->memberCard($trooper);
        }

        if ($members->isEmpty() && $command_staff->isEmpty())
        {
            $html .= '<p style="width:100%;text-align:center">No members to display.</p>';
        }

        $html .= '</div>';

        return response($html);
    }

    private function memberCard(Trooper $trooper): string
    {
        $name = e($trooper->display_name);
        $identifier = $trooper->organizations->first()?->pivot?->identifier ?? '';
        $org_id = $trooper->organizations->first()?->id;

        $formatted_id = $this->resolveDisplayPrefix($trooper, $org_id).$identifier;
        $label = $formatted_id ? $name.' - '.e($formatted_id) : $name;

        $photo = $trooper->trooper_costumes
            ->firstWhere(fn ($c) => !empty($c->{TrooperCostume::IMAGE_URL_SM}))
            ?->{TrooperCostume::IMAGE_URL_SM};

        $img = $photo
            ? '<img src="'.e($photo).'" alt="">'
            : '<img src="'.e(url('images/tk_head.jpg')).'" alt="">';

        return '<div class="member">'.$img.$label.'</div>';
    }

    private function resolveDisplayPrefix(Trooper $trooper, ?int $org_id): string
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

    private function slideshowResponse(array $uploads): Response
    {
        $html = '<script src="https://www.w3schools.com/lib/w3.js"></script>';

        foreach ($uploads as $item)
        {
            $filename = pathinfo($item['fileName'], PATHINFO_FILENAME);
            $html .= '<img class="slideshow" src="'.e(url('images/uploads/resize/'.$filename.'.jpg')).'" width="100%" height="100%">';
        }

        $html .= '<script>w3.slideshow(".slideshow", 3000);</script>';

        return response($html);
    }
}

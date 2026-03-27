<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\EventUpload;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Provides a public API for use by external developers.
 *
 * @author Matthew Drennan
 */
class PublicApiController
{
    public function __invoke(Request $request): JsonResponse|Response
    {
        if ($request->has('trooperid') || $request->has('tkid')) {
            return $this->trooperHistory($request);
        }

        if ($request->has('photos') && $request->has('amount')) {
            return $this->photos($request);
        }

        return response()->json([]);
    }

    private function trooperHistory(Request $request): JsonResponse
    {
        $trooper = null;

        if ($request->has('trooperid')) {
            $trooper = Trooper::find($request->integer('trooperid'));
        } elseif ($request->has('tkid') && $request->has('squad')) {
            $trooper_org = TrooperOrganization::where(TrooperOrganization::IDENTIFIER, $request->input('tkid'))
                ->where(TrooperOrganization::ORGANIZATION_ID, $request->integer('squad'))
                ->with('trooper')
                ->first();

            $trooper = $trooper_org?->trooper;
        }

        if (!$trooper) {
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
                'eventID'   => $et->event_shift->event->id,
                'eventName' => $et->event_shift->event->name,
                'dateStart' => $et->event_shift->event->event_start,
                'dateEnd'   => $et->event_shift->event->event_end,
            ])
            ->sortByDesc('dateEnd')
            ->values()
            ->all();

        $data = [[
            'trooperName' => $trooper->display_name,
            'tkid'        => $identifier,
            '501forum'    => null,
            'rebelforum'  => null,
            'events'      => $event_array,
            'troopCount'  => count($event_array),
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
            'fileName'  => $upload->image_path_sm,
            'troopID'   => $upload->event_id,
            'trooperID' => $upload->trooper_id,
        ])->toArray();

        if ($request->has('slideshow')) {
            return $this->slideshowResponse($upload_array);
        }

        // Double-wrapped intentionally: legacy API contract expects [[...items...]]
        return response()->json([$upload_array]);
    }

    private function slideshowResponse(array $uploads): Response
    {
        $html = '<script src="https://www.w3schools.com/lib/w3.js"></script>';

        foreach ($uploads as $item) {
            $filename = pathinfo($item['fileName'], PATHINFO_FILENAME);
            $html .= '<img class="slideshow" src="' . e(url('images/uploads/resize/' . $filename . '.jpg')) . '" width="100%" height="100%">';
        }

        $html .= '<script>w3.slideshow(".slideshow", 3000);</script>';

        return response($html);
    }
}

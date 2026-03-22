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

        if ($request->has('events')) {
            return $this->upcomingEvents();
        }

        return response()->json([]);
    }

    private function trooperHistory(Request $request): JsonResponse
    {
        $trooper = null;

        if ($request->has('trooperid')) {
            $trooper = Trooper::find($request->integer('trooperid'));
        } elseif ($request->has('tkid') && $request->has('squad')) {
            $trooperOrg = TrooperOrganization::where(TrooperOrganization::IDENTIFIER, $request->input('tkid'))
                ->where(TrooperOrganization::ORGANIZATION_ID, $request->integer('squad'))
                ->with('trooper')
                ->first();

            $trooper = $trooperOrg?->trooper;
        }

        if (!$trooper) {
            return response()->json([]);
        }

        $identifier = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->value(TrooperOrganization::IDENTIFIER);

        $eventTroopers = EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->with(['event_shift.event'])
            ->get();

        $eventArray = [];

        foreach ($eventTroopers as $eventTrooper) {
            $event = $eventTrooper->event_shift->event;

            if ($event->status === EventStatus::CLOSED) {
                $eventArray[] = [
                    'eventID'   => $event->id,
                    'eventName' => $event->name,
                    'dateStart' => $event->event_start,
                    'dateEnd'   => $event->event_end,
                ];
            }
        }

        usort($eventArray, fn ($a, $b) => $b['dateEnd'] <=> $a['dateEnd']);

        $data = [[
            'trooperName' => $trooper->display_name,
            'tkid'        => $identifier,
            '501forum'    => null,
            'rebelforum'  => null,
            'events'      => $eventArray,
            'troopCount'  => count($eventArray),
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

        $uploadArray = $uploads->map(fn ($upload) => [
            'fileName'  => $upload->image_path_sm,
            'troopID'   => $upload->event_id,
            'trooperID' => $upload->trooper_id,
        ])->toArray();

        if ($request->has('slideshow')) {
            return $this->slideshowResponse($uploadArray);
        }

        return response()->json([$uploadArray]);
    }

    private function upcomingEvents(): JsonResponse
    {
        $events = Event::whereIn(Event::STATUS, [EventStatus::OPEN->value, EventStatus::SIGN_UP_LOCKED->value])
            ->whereNotNull(Event::LATITUDE)
            ->whereNotNull(Event::LONGITUDE)
            ->where(Event::EVENT_START, '>=', now()->startOfDay())
            ->orderBy(Event::EVENT_START)
            ->get();

        $data = $events->map(fn ($event) => [
            'troopid'   => $event->id,
            'name'      => $event->name,
            'dateStart' => $event->event_start,
            'dateEnd'   => $event->event_end,
            'location'  => $event->venue,
            'latitude'  => $event->latitude,
            'longitude' => $event->longitude,
        ])->toArray();

        return response()->json($data);
    }

    private function slideshowResponse(array $uploads): Response
    {
        $html = '<script src="https://www.w3schools.com/lib/w3.js"></script>';

        foreach ($uploads as $item) {
            $filename = pathinfo($item['fileName'], PATHINFO_FILENAME);
            $html .= '<img class="slideshow" src="' . url('images/uploads/resize/' . $filename . '.jpg') . '" width="100%" height="100%">';
        }

        $html .= '<script>w3.slideshow(".slideshow", 3000);</script>';

        return response($html);
    }
}

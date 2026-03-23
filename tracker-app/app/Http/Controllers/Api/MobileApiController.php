<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventUpload;
use App\Models\MobileDevice;
use App\Models\OauthLogin;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperApiCode;
use App\Models\TrooperOrganization;
use App\Services\Mobile\MobileForumLoginException;
use App\Services\Mobile\MobileForumLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * API for mobile app troop data.
 *
 * @author Matthew Drennan
 */
class MobileApiController
{
    public function __construct(private readonly MobileForumLoginService $mobile_forum_login_service)
    {
    }

    /**
     * The active statuses representing a trooper who intends to attend
     * (not yet confirmed as attended, not cancelled).
     */
    private const ACTIVE_STATUSES = [
        EventTrooperStatus::GOING,
        EventTrooperStatus::STAND_BY,
        EventTrooperStatus::TENTATIVE,
        EventTrooperStatus::PENDING,
    ];

    /**
     * The event statuses considered open/upcoming.
     */
    private const OPEN_EVENT_STATUSES = [
        EventStatus::OPEN,
        EventStatus::SIGN_UP_LOCKED,
    ];

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $trooperId = (int) $request->input('trooperid', 0);

            $this->ensureTrooperIdMatch($request, $trooperId);

            $action = $request->input('action');

            return match (true) {
                $action === 'troops' && $request->has('user_id')
                    => $this->getTroops($request),

                $action === 'login_with_forum' && $request->has('access_token')
                    => $this->loginWithForum($request),

                $action === 'is_closed'
                    => $this->isClosed(),

                $action === 'user_status' && $request->has('trooperid')
                    => $this->getUserStatus($request),

                $action === 'event' && $request->has('troopid')
                    => $this->getEvent($request),

                $action === 'get_roster_for_event' && $request->has('troopid')
                    => $this->getRosterForEvent($request),

                $action === 'get_available_troopers_for_event' && $request->has('troopid')
                    => $this->getAvailableTroopersForEvent($request),

                $action === 'get_troops_by_squad' && $request->has('squad')
                    => $this->getTroopsBySquad($request),

                $action === 'get_squad_club_name' && $request->has('squad')
                    => $this->getSquadClubName($request),

                $action === 'get_costumes_for_trooper' && $request->has('trooperid')
                    => $this->getCostumesForTrooper($request),

                $action === 'set_status_costume' && $request->has('trooperid', 'troopid', 'status')
                    => $this->setStatusCostume($request),

                $action === 'get_confirm_events_trooper' && $request->has('trooperid')
                    => $this->getConfirmEventsTrooper($request),

                $action === 'trooper_in_event' && $request->has('trooperid', 'troopid')
                    => $this->trooperInEvent($request),

                $action === 'cancel_troop' && $request->has('trooperid', 'troopid')
                    => $this->cancelTroop($request),

                $action === 'sign_up' && $request->has('trooperid', 'troopid', 'addedby', 'status', 'costume', 'backupcostume')
                    => $this->signUp($request),

                $action === 'get_photos_by_event' && $request->has('troopid')
                    => $this->getPhotosByEvent($request),

                $action === 'saveFCM' && $request->has('userid', 'fcm')
                    => $this->saveFcm($request),

                $action === 'logoutFCM' && $request->has('fcm', 'apiKey')
                    => $this->logoutFcm($request),

                default => response()->json(['error' => 'Invalid request parameters.'], 400),
            };
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Authentication
    // -------------------------------------------------------------------------

    /**
     * Generate a unique 64-character API key for a trooper and store it.
     */
    private function generateApiKey(int $trooperId): string
    {
        do {
            $apiKey = bin2hex(random_bytes(32));
        } while (TrooperApiCode::where(TrooperApiCode::API_CODE, $apiKey)->exists());

        TrooperApiCode::create([
            TrooperApiCode::TROOPER_ID => $trooperId,
            TrooperApiCode::API_CODE   => $apiKey,
        ]);

        return $apiKey;
    }

    /**
     * Validate the API-Key header and return the associated trooper ID, or null.
     */
    private function validateApiKey(Request $request): ?int
    {
        $apiKey = $request->header('API-Key');

        if (empty($apiKey)) {
            return null;
        }

        return TrooperApiCode::where(TrooperApiCode::API_CODE, $apiKey)
            ->value(TrooperApiCode::TROOPER_ID);
    }

    /**
     * Abort with 403 if the API key is valid but belongs to a different trooper.
     * Skipped when trooperId is 0.
     */
    private function ensureTrooperIdMatch(Request $request, int $trooperId): void
    {
        if ($trooperId === 0) {
            return;
        }

        $associatedId = $this->validateApiKey($request);

        if ($associatedId !== null && $associatedId !== $trooperId) {
            abort(response()->json(['error' => 'You are not authorized to modify this trooper ID.'], 403));
        }
    }

    /**
     * Resolve a trooper by their Xenforo OAuth provider_id (forum user_id).
     */
    private function trooperFromUserId(int $userId): ?Trooper
    {
        $oauth = OauthLogin::where(OauthLogin::PROVIDER, 'xenforo')
            ->where(OauthLogin::PROVIDER_ID, (string) $userId)
            ->with('trooper')
            ->first();

        return $oauth?->trooper;
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    /**
     * action=login_with_forum
     * Authenticate via Xenforo OAuth and return an API key.
     */
    private function loginWithForum(Request $request): JsonResponse
    {
        try {
            $access_token = (string) $request->input('access_token', '');

            $authenticated = $this->mobile_forum_login_service
                ->authenticate($access_token);

            $trooper = $authenticated['trooper'];
            $forum_user = $authenticated['forum_user'];
            $trooper->touch(Trooper::LAST_ACTIVE_AT);

            return response()->json([
                'success' => true,
                'apiKey' => $this->generateApiKey($trooper->id),
                'user' => [
                    'user_id' => (string) ($forum_user['user_id'] ?? ''),
                    'username' => (string) ($forum_user['username'] ?? $trooper->display_name),
                    'avatar_urls' => $forum_user['avatar_urls'] ?? [],
                ],
            ]);
        } catch (MobileForumLoginException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], $e->statusCode());
        }
    }

    /**
     * action=is_closed
     * Return whether the site is currently closed to sign-ups.
     */
    private function isClosed(): JsonResponse
    {
        // TODO: The old system stored site_closed/sitemessage in a settings
        // table that does not exist in the new schema. Replace with the
        // appropriate config or database lookup once that mechanism is defined.
        return response()->json([
            'isWebsiteClosed' => false,
            'siteMessage'     => '',
        ]);
    }

    /**
     * action=user_status
     * Return access/ban status for a trooper identified by their Xenforo user_id.
     */
    private function getUserStatus(Request $request): JsonResponse
    {
        $userId   = (int) $request->input('trooperid');
        $trooper  = $this->trooperFromUserId($userId);

        if (!$trooper) {
            return response()->json(['error' => 'Trooper not found.'], 404);
        }

        $trooper->touch(Trooper::LAST_ACTIVE_AT);

        return response()->json([
            'forum_id'  => null, // forum_id no longer stored directly on trooper
            'canAccess' => $trooper->membership_status === MembershipStatus::ACTIVE,
            'isBanned'  => false, // TODO: retrieve ban status from Xenforo OAuth provider if needed
        ]);
    }

    /**
     * action=troops
     * Return upcoming events that the given user (Xenforo user_id) is signed up for.
     */
    private function getTroops(Request $request): JsonResponse
    {
        $userId  = (int) $request->input('user_id');
        $trooper = $this->trooperFromUserId($userId);

        if (!$trooper) {
            return response()->json(['troops' => []]);
        }

        $activeStatusValues = array_map(fn ($s) => $s->value, self::ACTIVE_STATUSES);
        $openStatusValues   = array_map(fn ($s) => $s->value, self::OPEN_EVENT_STATUSES);

        $eventTroopers = EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->whereIn(EventTrooper::STATUS, $activeStatusValues)
            ->with(['event_shift.event'])
            ->whereHas('event_shift.event', fn ($q) => $q
                ->whereIn(Event::STATUS, $openStatusValues)
                ->where(Event::EVENT_END, '>', now()->subDay())
            )
            ->get();

        $troops = $eventTroopers
            ->sortBy(fn ($et) => $et->event_shift->event->event_start)
            ->map(fn ($et) => $this->buildTroopObject($et->event_shift->event))
            ->unique('troopid')
            ->values();

        return response()->json(['troops' => $troops]);
    }

    /**
     * action=event
     * Return full details for a single event.
     */
    private function getEvent(Request $request): JsonResponse
    {
        $event = Event::with(['event_shifts.event_troopers'])->find((int) $request->input('troopid'));

        if (!$event) {
            return response()->json(['error' => 'Event not found.'], 404);
        }

        $trooperCount = $event->event_shifts->flatMap->event_troopers
            ->whereIn('status', array_map(fn ($s) => $s->value, [
                EventTrooperStatus::GOING,
                EventTrooperStatus::STAND_BY,
                EventTrooperStatus::TENTATIVE,
            ]))->count();

        $handlerCount = $event->event_shifts->flatMap->event_troopers
            ->whereIn('status', array_map(fn ($s) => $s->value, [
                EventTrooperStatus::GOING,
                EventTrooperStatus::STAND_BY,
                EventTrooperStatus::TENTATIVE,
            ]))->where('is_handler', true)->count();

        $troopersAllowed = $event->troopers_allowed ?? 500;
        $handlersAllowed = $event->handlers_allowed ?? 500;
        $isLimited       = $troopersAllowed < 500 || $handlersAllowed < 500;
        $location = collect([
            $event->venue_address,
            $event->venue_city,
            $event->venue_state,
            $event->venue_zip,
            $event->venue_country,
        ])->filter()->implode(', ');

        $limitClubs = '';
        if ($isLimited) {
            if ($troopersAllowed < 500) {
                $remaining   = max(0, $troopersAllowed - $trooperCount);
                $limitClubs .= "This event is limited to {$troopersAllowed} troopers. {$remaining} troopers remaining.\n";
            }
            if ($handlersAllowed < 500) {
                $remaining   = max(0, $handlersAllowed - $handlerCount);
                $limitClubs .= "This event is limited to {$handlersAllowed} handlers. {$remaining} handlers remaining.\n";
            }
        }

        return response()->json(array_merge(
            [
                'id'             => $event->id,
                'name'           => $event->name,
                'dateStart'      => $event->event_start?->format('Y-m-d H:i:s'),
                'dateEnd'        => $event->event_end?->format('Y-m-d H:i:s'),
                'venue'          => $event->venue,
                'location'       => $location !== '' ? $location : $event->venue,
                'website'        => $event->event_website,
                'comments'       => $event->comments,
                'thread_id'      => $event->thread_id,
                'post_id'        => $event->post_id,
                'squad'          => $event->organization_id,
                'closed'         => $event->status->value,
                'numberOfAttend' => $event->expected_attendees,
                'requestedNumber' => $event->requested_number_characters,
                'requestedCharacter' => $event->requested_character_types,
                'secureChanging' => (int) $event->secure_staging_area,
                'blasters'       => (int) $event->allow_blasters,
                'lightsabers'    => (int) $event->allow_props,
                'parking'        => (int) $event->parking_available,
                'mobility'       => (int) $event->accessible,
                'amenities'      => $event->amenities,
                'referred'       => $event->referred_by,
                'limitedEvent'   => (int) $isLimited,
                'allowTentative' => (int) $event->tentative_signups_allowed,
                'limitTotalTroopers' => $troopersAllowed,
                'limitHandlers'  => $handlersAllowed,
            ],
            [
                'isLimited'    => $isLimited,
                'limitTotal'   => $troopersAllowed,
                'limitClubs'   => $limitClubs,
                'trooper_count'=> $trooperCount,
                'num_of_handlers' => $handlerCount,
            ]
        ));
    }

    /**
     * action=get_roster_for_event
     * Return all sign-ups (event_troopers) for an event with costume and trooper info.
     */
    private function getRosterForEvent(Request $request): JsonResponse
    {
        $eventId = (int) $request->input('troopid');

        $eventTroopers = EventTrooper::whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $eventId))
            ->with(['trooper.organizations', 'costume', 'backup_costume', 'event_shift'])
            ->get();

        $roster = $eventTroopers->map(function ($et) {
            $trooper = $et->trooper;
            $organization = $trooper?->organizations->first();
            $identifier = $organization?->pivot?->identifier;

            return [
                'id'                  => $et->id,
                'trooperid'           => $et->trooper_id,
                'troopid'             => $et->event_shift->event_id,
                'status'              => $et->status->value,
                'status_formatted'    => $this->formatStatus($et->status),
                'costume'             => $et->costume_id,
                'costume_name'        => $et->costume?->name,
                'backup_costume'      => $et->backup_costume_id,
                'backup_costume_name' => $et->backup_costume?->name,
                'is_handler'          => $et->is_handler,
                'trooper_name'        => $trooper?->display_name,
                'tkid'                => $identifier,
                'tkid_formatted'      => $identifier,
                'squad'               => $organization?->id,
                'signuptime'          => $et->signed_up_at?->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json($roster);
    }

    /**
     * action=get_available_troopers_for_event
     * Return troopers who are NOT already signed up for the given event.
     */
    private function getAvailableTroopersForEvent(Request $request): JsonResponse
    {
        $eventId = (int) $request->input('troopid');

        $signedUpTrooperIds = EventTrooper::whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $eventId))
            ->pluck(EventTrooper::TROOPER_ID);

        $troopers = Trooper::whereNotIn(Trooper::ID, $signedUpTrooperIds)
            ->with('organizations')
            ->orderBy(Trooper::DISPLAY_NAME)
            ->get();

        $data = $troopers->map(function ($trooper) {
            $organization = $trooper->organizations->first();
            $identifier = $organization?->pivot?->identifier;

            return [
                'id'             => $trooper->id,
                'display_name'   => $trooper->display_name,
                'tkid'           => $identifier,
                'tkid_formatted' => $identifier,
                'squad'          => $organization?->id,
            ];
        });

        return response()->json($data);
    }

    /**
     * action=get_troops_by_squad
     * Return upcoming events for a given organization (squad), with sign-up counts and notices.
     */
    private function getTroopsBySquad(Request $request): JsonResponse
    {
        // TODO: implement isWebsiteClosed() check once that mechanism is defined.

        $squadId          = (int) $request->input('squad');
        $openStatusValues = array_map(fn ($s) => $s->value, self::OPEN_EVENT_STATUSES);

        $events = Event::whereIn(Event::STATUS, $openStatusValues)
            ->where(Event::EVENT_START, '>=', now()->startOfDay())
            ->when($squadId !== 0, fn ($q) => $q->where(Event::ORGANIZATION_ID, $squadId))
            ->with(['event_shifts.event_troopers'])
            ->orderBy(Event::EVENT_START)
            ->get();

        $troops = $events->map(function ($event) {
            $allTroopers = $event->event_shifts->flatMap->event_troopers
                ->whereIn('status', array_map(fn ($s) => $s->value, [
                    EventTrooperStatus::GOING,
                    EventTrooperStatus::STAND_BY,
                    EventTrooperStatus::TENTATIVE,
                ]));

            $trooperCount = $allTroopers->where('is_handler', false)->count();
            $handlerCount = $allTroopers->where('is_handler', true)->count();

            $troopersAllowed = $event->troopers_allowed ?? 500;
            $handlersAllowed = $event->handlers_allowed ?? 500;

            $notice = $this->buildNotice($event, $trooperCount, $handlerCount, $troopersAllowed, $handlersAllowed);

            return array_merge($this->buildTroopObject($event), [
                'trooper_count'  => $trooperCount,
                'num_of_handlers'=> $handlerCount,
                'notice'         => $notice,
            ]);
        });

        return response()->json(['troops' => $troops]);
    }

    /**
     * action=get_squad_club_name
     * Return the name of an organization and all organizations in the system.
     */
    private function getSquadClubName(Request $request): JsonResponse
    {
        $squadId      = (int) $request->input('squad');
        $organization = Organization::find($squadId);

        $allOrganizations = Organization::orderBy(Organization::NAME)->get();

        return response()->json([
            'squadName'  => $organization?->name ?? '',
            'squadNames' => $allOrganizations->pluck(Organization::NAME)->values(),
            'clubNames'  => $allOrganizations->pluck(Organization::NAME)->values(),
        ]);
    }

    /**
     * action=get_costumes_for_trooper
     * Return costumes available to a trooper.
     */
    private function getCostumesForTrooper(Request $request): JsonResponse
    {
        $userId   = (int) $request->input('trooperid');
        $friendId = (int) $request->input('friendid', 0);

        if ($userId > 0) {
            $trooper = $this->trooperFromUserId($userId);
        } elseif ($friendId > 0) {
            $trooper = Trooper::find($friendId);
        } else {
            $trooper = null;
        }

        if (!$trooper) {
            return response()->json([]);
        }

        // Get organization IDs the trooper belongs to
        $organizationIds = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->pluck(TrooperOrganization::ORGANIZATION_ID)
            ->toArray();

        // TODO: The old query used complex club-based costume restriction logic
        // (mainCostumes, mainCostumesQuery, costume_restrict_query). Replace
        // with Costume::forTrooper() once organization_ids are properly resolved.
        $costumes = Costume::whereHas('organizations', fn ($q) => $q->whereIn('tt_organizations.id', $organizationIds))
            ->orderBy(Costume::NAME)
            ->get();

        $data = $costumes->map(fn ($costume) => [
            'id'           => $costume->id,
            'name'         => $costume->name,
            'abbreviation' => null, // TODO: resolve from OrganizationCostume::PREFIX if needed
            'club'         => null, // TODO: resolve organization name if needed
        ]);

        return response()->json($data);
    }

    /**
     * action=set_status_costume
     * Update the status and/or costume for a trooper's sign-up on an event.
     *
     * NOTE: The new schema links sign-ups to event shifts, not events directly.
     * This updates ALL of the trooper's shift sign-ups for the given event.
     */
    private function setStatusCostume(Request $request): JsonResponse
    {
        $userId    = (int) $request->input('trooperid');
        $trooper   = $this->trooperFromUserId($userId);
        $eventId   = (int) $request->input('troopid');
        $newStatus = EventTrooperStatus::from($request->input('status'));
        $costumeId = (int) $request->input('costume', 0);

        if (!$trooper) {
            return response()->json(['error' => 'Trooper not found.'], 404);
        }

        $eventTroopers = EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $eventId))
            ->get();

        foreach ($eventTroopers as $et) {
            $et->status = $newStatus;
            if ($costumeId > 0) {
                $et->costume_id = $costumeId;
            }
            $et->save();
        }

        return response()->json(['success' => true]);
    }

    /**
     * action=get_confirm_events_trooper
     * Return past events where the trooper has not yet confirmed attendance.
     */
    private function getConfirmEventsTrooper(Request $request): JsonResponse
    {
        $userId  = (int) $request->input('trooperid');
        $trooper = $this->trooperFromUserId($userId);

        if (!$trooper) {
            return response()->json(['troops' => []]);
        }

        $activeStatusValues = array_map(fn ($s) => $s->value, [
            EventTrooperStatus::GOING,
            EventTrooperStatus::STAND_BY,
            EventTrooperStatus::TENTATIVE,
        ]);

        $eventTroopers = EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->whereIn(EventTrooper::STATUS, $activeStatusValues)
            ->with(['event_shift.event'])
            ->whereHas('event_shift.event', fn ($q) => $q
                ->where(Event::STATUS, EventStatus::CLOSED->value)
                ->where(Event::EVENT_END, '<', now())
            )
            ->get();

        $troops = $eventTroopers
            ->sortByDesc(fn ($et) => $et->event_shift->event->event_end)
            ->map(fn ($et) => $this->buildTroopObject($et->event_shift->event))
            ->unique('troopid')
            ->values();

        return response()->json(['troops' => $troops]);
    }

    /**
     * action=trooper_in_event
     * Check if a trooper is currently signed up for an event (non-cancelled).
     */
    private function trooperInEvent(Request $request): JsonResponse
    {
        $userId  = (int) $request->input('trooperid');
        $trooper = $this->trooperFromUserId($userId);
        $eventId = (int) $request->input('troopid');

        if (!$trooper) {
            return response()->json(['inEvent' => false]);
        }

        $inEvent = EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->where(EventTrooper::STATUS, '!=', EventTrooperStatus::CANCELLED->value)
            ->whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $eventId))
            ->exists();

        return response()->json(['inEvent' => $inEvent]);
    }

    /**
     * action=cancel_troop
     * Cancel all of a trooper's sign-ups for an event.
     */
    private function cancelTroop(Request $request): JsonResponse
    {
        $userId  = (int) $request->input('trooperid');
        $trooper = $this->trooperFromUserId($userId);
        $eventId = (int) $request->input('troopid');

        if (!$trooper) {
            return response()->json(['error' => 'Trooper not found.'], 404);
        }

        EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $eventId))
            ->update([EventTrooper::STATUS => EventTrooperStatus::CANCELLED->value]);

        // Queue notification for sign-up change
        EventNotification::firstOrCreate([
            EventNotification::EVENT_ID   => $eventId,
            EventNotification::TROOPER_ID => $trooper->id,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * action=sign_up
     * Sign a trooper up for an event (or update their existing sign-up).
     *
     * NOTE: The new schema requires signing up for a specific shift. This
     * endpoint signs the trooper up for the first available shift on the event.
     * If the trooper is already signed up, their record is updated instead.
     */
    private function signUp(Request $request): JsonResponse
    {
        $eventId        = (int) $request->input('troopid');
        $addedById      = (int) $request->input('addedby', 0);
        $costumeId      = (int) $request->input('costume', 0);
        $backupCostumeId= (int) $request->input('backupcostume', 0);
        $requestedStatus= EventTrooperStatus::from($request->input('status'));

        // Resolve trooper: if addedby > 0 it's an admin signing someone up
        if ($addedById > 0) {
            $trooperId = (int) $request->input('trooperid');
            $trooper   = Trooper::find($trooperId);
            $addedBy   = $this->trooperFromUserId($addedById);
        } else {
            $userId  = (int) $request->input('trooperid');
            $trooper = $this->trooperFromUserId($userId);
            $addedBy = null;
        }

        if (!$trooper) {
            return response()->json(['success' => 'fail', 'success_message' => 'Trooper not found.']);
        }

        // TODO: implement isWebsiteClosed() check once that mechanism is defined.

        $event = Event::find($eventId);
        if (!$event) {
            return response()->json(['success' => 'fail', 'success_message' => 'Event not found.']);
        }

        if ($event->status === EventStatus::CANCELLED) {
            return response()->json(['success' => 'fail', 'success_message' => 'This event was CANCELED by Command Staff.']);
        }

        if ($event->status === EventStatus::SIGN_UP_LOCKED) {
            return response()->json(['success' => 'fail', 'success_message' => 'This event was LOCKED by Command Staff.']);
        }

        // Check for existing sign-up and update it
        $existing = EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $eventId))
            ->first();

        if ($existing) {
            $existing->update([
                EventTrooper::STATUS           => $requestedStatus->value,
                EventTrooper::COSTUME_ID       => $costumeId ?: null,
                EventTrooper::BACKUP_COSTUME_ID=> $backupCostumeId ?: null,
            ]);

            EventNotification::firstOrCreate([
                EventNotification::EVENT_ID   => $eventId,
                EventNotification::TROOPER_ID => $trooper->id,
            ]);

            return response()->json(['success' => 'success', 'success_message' => 'Success!']);
        }

        // Capacity check
        $status = $requestedStatus;

        if ($costumeId) {
            $costume    = Costume::find($costumeId);
            $isHandler  = $costume && Str::contains(strtolower($costume->name), 'handler');
        } else {
            $isHandler = false;
        }

        if ($isHandler) {
            $handlersAllowed = $event->handlers_allowed ?? 500;
            $handlerCount    = EventTrooper::whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $eventId))
                ->where(EventTrooper::IS_HANDLER, true)
                ->whereIn(EventTrooper::STATUS, array_map(fn ($s) => $s->value, [EventTrooperStatus::GOING, EventTrooperStatus::STAND_BY, EventTrooperStatus::TENTATIVE]))
                ->count();

            if ($handlersAllowed < 500 && $handlerCount >= $handlersAllowed && $status !== EventTrooperStatus::CANCELLED) {
                $status = EventTrooperStatus::STAND_BY;
            }
        } else {
            $troopersAllowed = $event->troopers_allowed ?? 500;
            $trooperCount    = EventTrooper::whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $eventId))
                ->where(EventTrooper::IS_HANDLER, false)
                ->whereIn(EventTrooper::STATUS, array_map(fn ($s) => $s->value, [EventTrooperStatus::GOING, EventTrooperStatus::STAND_BY, EventTrooperStatus::TENTATIVE]))
                ->count();

            if ($troopersAllowed < 500 && $trooperCount >= $troopersAllowed && $status !== EventTrooperStatus::CANCELLED) {
                $status = EventTrooperStatus::STAND_BY;
            }
        }

        // Find the first available shift for this event
        $shift = EventShift::where(EventShift::EVENT_ID, $eventId)->first();

        if (!$shift) {
            return response()->json(['success' => 'fail', 'success_message' => 'No shifts available for this event.']);
        }

        EventTrooper::create([
            EventTrooper::EVENT_SHIFT_ID     => $shift->id,
            EventTrooper::TROOPER_ID         => $trooper->id,
            EventTrooper::COSTUME_ID         => $costumeId ?: null,
            EventTrooper::BACKUP_COSTUME_ID  => $backupCostumeId ?: null,
            EventTrooper::STATUS             => $status->value,
            EventTrooper::IS_HANDLER         => $isHandler,
            EventTrooper::SIGNED_UP_AT       => now(),
        ]);

        EventNotification::firstOrCreate([
            EventNotification::EVENT_ID   => $eventId,
            EventNotification::TROOPER_ID => $trooper->id,
        ]);

        return response()->json(['success' => 'success', 'success_message' => 'Success!']);
    }

    /**
     * action=get_photos_by_event
     * Return all photos for an event, admin photos first.
     */
    private function getPhotosByEvent(Request $request): JsonResponse
    {
        $eventId = (int) $request->input('troopid');

        $uploads = EventUpload::where(EventUpload::EVENT_ID, $eventId)
            ->with('trooper')
            ->orderByDesc(EventUpload::IS_ADMINISTRATIVE)
            ->orderByDesc(EventUpload::CREATED_AT)
            ->get();

        $photos = $uploads->map(fn ($upload) => [
            'id'            => $upload->id,
            'filename'      => $upload->image_path_sm,
            'admin'         => (int) $upload->is_administrative,
            'thumbnail_url' => $upload->small_url,
            'full_url'      => $upload->large_url,
            'uploaded_by'   => $upload->trooper?->display_name,
        ]);

        return response()->json(['photos' => $photos]);
    }

    /**
     * action=saveFCM
     * Save an FCM push notification token for a trooper.
     */
    private function saveFcm(Request $request): JsonResponse
    {
        $userId   = (int) $request->input('userid');
        $fcmToken = $request->input('fcm');
        $trooper  = $this->trooperFromUserId($userId);

        $exists = MobileDevice::where(MobileDevice::FCM_TOKEN, $fcmToken)->exists();

        if (!$exists) {
            MobileDevice::create([
                MobileDevice::TROOPER_ID => $trooper?->id,
                MobileDevice::FCM_TOKEN  => $fcmToken,
            ]);

            return response()->json(['success' => 'Record created!']);
        }

        return response()->json(['success' => 'Record exists!']);
    }

    /**
     * action=logoutFCM
     * Delete the FCM token and revoke the API key on logout.
     */
    private function logoutFcm(Request $request): JsonResponse
    {
        $fcmToken = $request->input('fcm');
        $apiKey   = $request->input('apiKey');

        \DB::transaction(function () use ($fcmToken, $apiKey) {
            MobileDevice::where(MobileDevice::FCM_TOKEN, $fcmToken)->delete();
            TrooperApiCode::where(TrooperApiCode::API_CODE, $apiKey)->delete();
        });

        return response()->json(['success' => 'Records deleted!']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a standard troop/event object for list responses.
     */
    private function buildTroopObject(Event $event): array
    {
        return [
            'troopid'   => $event->id,
            'name'      => $event->name,
            'dateStart' => $event->event_start,
            'dateEnd'   => $event->event_end,
            'location'  => $event->venue,
            'thread_id' => $event->thread_id,
            'post_id'   => $event->post_id,
            'squad'     => $event->organization_id,
        ];
    }

    /**
     * Build a notice string for an event based on capacity.
     */
    private function buildNotice(Event $event, int $trooperCount, int $handlerCount, int $troopersAllowed, int $handlersAllowed): string
    {
        if ($event->status === EventStatus::SIGN_UP_LOCKED) {
            return 'THIS TROOP IS FULL!';
        }

        if ($trooperCount <= 1) {
            return 'NOT ENOUGH TROOPERS FOR THIS EVENT!';
        }

        $troopersFull = $troopersAllowed < 500 && $trooperCount >= $troopersAllowed;
        $handlersFull = $handlersAllowed < 500 && $handlerCount >= $handlersAllowed;

        if ($troopersFull && ($handlersAllowed >= 500 || $handlersFull)) {
            return 'THIS TROOP IS FULL!';
        }

        return '';
    }

    /**
     * Return a human-readable label for an EventTrooperStatus.
     */
    private function formatStatus(EventTrooperStatus $status): string
    {
        return match ($status) {
            EventTrooperStatus::GOING            => 'Going',
            EventTrooperStatus::STAND_BY         => 'Stand By',
            EventTrooperStatus::TENTATIVE        => 'Tentative',
            EventTrooperStatus::ATTENDED         => 'Attended',
            EventTrooperStatus::CANCELLED        => 'Cancelled',
            EventTrooperStatus::PENDING          => 'Pending',
            EventTrooperStatus::NOT_PICKED       => 'Not Picked',
            EventTrooperStatus::NO_SHOW          => 'No Show',
            EventTrooperStatus::UNABLE_TO_ATTEND => 'Unable to Attend',
            default                              => 'Unknown',
        };
    }
}

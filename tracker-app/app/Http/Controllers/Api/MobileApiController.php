<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipStatus;
use App\Enums\OauthProvider;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventGuest;
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
use App\Services\Forums\XenforoService;
use App\Services\Mobile\MobileForumLoginException;
use App\Services\Mobile\MobileForumLoginService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * API for mobile app troop data.
 *
 * @author Matthew Drennan
 */
class MobileApiController
{
    public function __construct(
        private readonly MobileForumLoginService $mobile_forum_login_service,
        private readonly XenforoService $xenforo,
    ) {}

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
        EventStatus::MANUAL_SELECTION,
        EventStatus::SIGN_UP_LOCKED,
    ];

    public function __invoke(Request $request): JsonResponse
    {
        try
        {
            $trooper_id = (int) $request->input('trooperid', 0);
            $added_by_id = (int) $request->input('addedby', 0);

            // When signing someone else up (addedby present), skip the trooperid
            // ownership check — the API key alone is sufficient auth, and the
            // sign_up handler validates the addedby trooper internally.
            $this->ensureTrooperIdMatch($request, $added_by_id > 0 ? 0 : $trooper_id);

            $action = $request->input('action');

            return match (true)
            {
                $action === 'troops' && $request->has('user_id') => $this->getTroops($request),

                $action === 'login_with_forum' && $request->has('access_token') => $this->loginWithForum($request),

                $action === 'is_closed' => $this->isClosed(),

                $action === 'user_status' && $request->has('trooperid') => $this->getUserStatus($request),

                $action === 'event' && $request->has('troopid') => $this->getEvent($request),

                $action === 'get_roster_for_event' && $request->has('troopid') => $this->getRosterForEvent($request),

                $action === 'get_available_troopers_for_event' && $request->has('troopid') => $this->getAvailableTroopersForEvent($request),

                $action === 'get_troops_by_squad' && $request->has('squad') => $this->getTroopsBySquad($request),

                $action === 'get_squad_club_name' && $request->has('squad') => $this->getSquadClubName($request),

                $action === 'get_costumes_for_trooper' && $request->has('trooperid') => $this->getCostumesForTrooper($request),

                $action === 'set_status_costume' && $request->has('trooperid', 'troopid', 'status') => $this->setStatusCostume($request),

                $action === 'get_confirm_events_trooper' && $request->has('trooperid') => $this->getConfirmEventsTrooper($request),

                $action === 'trooper_in_event' && $request->has('trooperid', 'troopid') => $this->trooperInEvent($request),

                $action === 'cancel_troop' && $request->has('trooperid', 'troopid') => $this->cancelTroop($request),

                $action === 'cancel_shift' && $request->has('trooperid', 'shiftid') => $this->cancelShift($request),

                $action === 'get_friends_for_event' && $request->has('trooperid', 'troopid') => $this->getFriendsForEvent($request),

                $action === 'get_guests_for_event' && $request->has('trooperid', 'troopid') => $this->getGuestsForEvent($request),

                $action === 'add_guest' && $request->has('trooperid', 'troopid', 'name') => $this->addGuest($request),

                $action === 'cancel_guest' && $request->has('trooperid', 'guestid') => $this->cancelGuest($request),

                $action === 'sign_up' && $request->has('trooperid', 'troopid', 'addedby', 'status', 'costume', 'backupcostume') => $this->signUp($request),

                $action === 'get_photos_by_event' && $request->has('troopid') => $this->getPhotosByEvent($request),

                $action === 'saveFCM' && $request->has('userid', 'fcm') => $this->saveFcm($request),

                $action === 'logoutFCM' && $request->has('fcm', 'apiKey') => $this->logoutFcm($request),

                default => response()->json(['error' => 'Invalid request parameters.'], 400),
            };
        }
        catch (\InvalidArgumentException $e)
        {
            return response()->json(['error' => $e->getMessage()], 400);
        }
        catch (HttpResponseException $e)
        {
            $response = $e->getResponse();

            if ($response instanceof JsonResponse)
            {
                return $response;
            }

            return response()->json(['error' => $response->getContent()], $response->getStatusCode());
        }
        catch (\Throwable $e)
        {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Authentication
    // -------------------------------------------------------------------------

    /**
     * Generate a unique 64-character API key for a trooper and store it.
     */
    private function generateApiKey(int $trooper_id): string
    {
        do
        {
            $api_key = bin2hex(random_bytes(32));
        }
        while (TrooperApiCode::where(TrooperApiCode::API_CODE, $api_key)->exists());

        TrooperApiCode::create([
            TrooperApiCode::TROOPER_ID => $trooper_id,
            TrooperApiCode::API_CODE => $api_key,
        ]);

        return $api_key;
    }

    /**
     * Validate the API-Key header and return the associated trooper ID, or null.
     */
    private function validateApiKey(Request $request): ?int
    {
        $api_key = $request->header('API-Key');

        if (empty($api_key))
        {
            return null;
        }

        return TrooperApiCode::where(TrooperApiCode::API_CODE, $api_key)
            ->value(TrooperApiCode::TROOPER_ID);
    }

    /**
     * Abort with 403 if the API key is valid but belongs to a different trooper.
     * Skipped when trooper_id is 0.
     */
    private function ensureTrooperIdMatch(Request $request, int $trooper_id): void
    {
        if ($trooper_id === 0)
        {
            return;
        }

        $associated_id = $this->validateApiKey($request);

        if ($associated_id === null)
        {
            return;
        }

        if ($associated_id === $trooper_id)
        {
            return;
        }

        $resolved_trooper_id = $this->trooperFromUserId($trooper_id)?->id;

        if ($resolved_trooper_id !== null && $associated_id === $resolved_trooper_id)
        {
            return;
        }

        abort(response()->json(['error' => 'You are not authorized to modify this trooper ID.'], 403));
    }

    /**
     * Resolve a trooper by their Xenforo OAuth provider_id (forum user_id).
     */
    private function trooperFromUserId(int $user_id): ?Trooper
    {
        $oauth = OauthLogin::where(OauthLogin::PROVIDER, OauthProvider::XENFORO)
            ->where(OauthLogin::PROVIDER_ID, (string) $user_id)
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
        try
        {
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
        }
        catch (MobileForumLoginException $e)
        {
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
            'siteMessage' => '',
        ]);
    }

    /**
     * action=user_status
     * Return access/ban status for a trooper identified by their Xenforo user_id.
     */
    private function getUserStatus(Request $request): JsonResponse
    {
        $user_id = (int) $request->input('trooperid');
        $trooper = $this->trooperFromUserId($user_id);

        if (!$trooper)
        {
            return response()->json(['error' => 'Trooper not found.'], 404);
        }

        $trooper->touch(Trooper::LAST_ACTIVE_AT);

        $isBanned = false;

        $xenforo_user_id = $this->xenforo->resolve_user_id_for_trooper($trooper->id);

        if ($xenforo_user_id !== null)
        {
            $result = $this->xenforo->get_user($xenforo_user_id);

            if ($result['status'] === 200 && isset($result['body']['user']))
            {
                $isBanned = !empty($result['body']['user']['is_banned']);
            }
        }

        return response()->json([
            'forum_id' => null,
            'canAccess' => $trooper->membership_status === MembershipStatus::ACTIVE,
            'isBanned' => $isBanned,
        ]);
    }

    /**
     * action=troops
     * Return upcoming events that the given user (Xenforo user_id) is signed up for.
     */
    private function getTroops(Request $request): JsonResponse
    {
        $user_id = (int) $request->input('user_id');
        $trooper = $this->trooperFromUserId($user_id);

        if (!$trooper)
        {
            return response()->json(['troops' => []]);
        }

        $active_status_values = array_map(fn ($s) => $s->value, self::ACTIVE_STATUSES);
        $open_status_values = array_map(fn ($s) => $s->value, self::OPEN_EVENT_STATUSES);

        $event_troopers = EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->whereIn(EventTrooper::STATUS, $active_status_values)
            ->with(['event_shift.event'])
            ->whereHas('event_shift.event', fn ($q) => $q
                ->whereIn(Event::STATUS, $open_status_values)
                ->where(Event::EVENT_END, '>', now()->subDay())
            )
            ->get();

        $troops = $event_troopers
            ->groupBy(fn ($et) => $et->event_shift->event_id)
            ->map(function ($ets) {
                $event = $ets->first()->event_shift->event;
                $my_shifts = $ets
                    ->sortBy(fn ($et) => $et->event_shift->shift_starts_at)
                    ->map(fn ($et) => [
                        'shift_id' => $et->event_shift_id,
                        'display' => $et->event_shift->time_display,
                        'status' => $this->formatStatus($et->status),
                    ])
                    ->values();

                return array_merge($this->buildTroopObject($event), [
                    'my_shifts' => $my_shifts,
                ]);
            })
            ->sortBy('dateStart')
            ->values();

        return response()->json(['troops' => $troops]);
    }

    /**
     * action=event
     * Return full details for a single event.
     */
    private function getEvent(Request $request): JsonResponse
    {
        $event = Event::with(['event_shifts.event_troopers', 'event_shifts.event_guests'])
            ->find((int) $request->input('troopid'));

        if (!$event)
        {
            return response()->json(['error' => 'Event not found.'], 404);
        }

        [$trooper_count, $handler_count] = $this->countActiveAttendees($event);

        $user_id = (int) $request->input('trooperid', 0);
        $trooper = $user_id > 0 ? $this->trooperFromUserId($user_id) : null;

        $troopers_allowed = $event->troopers_allowed;
        $handlers_allowed = $event->handlers_allowed;
        $is_limited = $troopers_allowed !== null || $handlers_allowed !== null;
        $location = $this->buildEventLocation($event);
        $limit_clubs = $this->buildCapacityMessage($troopers_allowed, $handlers_allowed, $trooper_count, $handler_count);

        return response()->json(array_merge(
            [
                'id' => $event->id,
                'name' => $event->name,
                'dateStart' => $event->event_start?->format('Y-m-d H:i:s'),
                'dateEnd' => $event->event_end?->format('Y-m-d H:i:s'),
                'venue' => $event->venue,
                'location' => $location !== '' ? $location : $event->venue,
                'website' => $event->event_website,
                'comments' => $event->comments,
                'thread_id' => $event->thread_id,
                'post_id' => $event->post_id,
                'squad' => $event->organization_id,
                'closed' => $event->status->value,
                'numberOfAttend' => $event->expected_attendees,
                'requestedNumber' => $event->requested_number_characters,
                'requestedCharacter' => $event->requested_character_types,
                'secureChanging' => (int) $event->secure_staging_area,
                'blasters' => (int) $event->allow_blasters,
                'lightsabers' => (int) $event->allow_props,
                'parking' => (int) $event->parking_available,
                'mobility' => (int) $event->accessible,
                'amenities' => $event->amenities,
                'referred' => $event->referred_by,
                'limitedEvent' => (int) $is_limited,
                'allowTentative' => (int) $event->tentative_signups_allowed,
                'limitTotalTroopers' => $troopers_allowed,
                'limitHandlers' => $handlers_allowed,
                'guests_allowed' => $event->guests_allowed,
                'friends_allowed' => $event->friends_allowed,
            ],
            [
                'isLimited' => $is_limited,
                'limitTotal' => $troopers_allowed,
                'limitClubs' => $limit_clubs,
                'trooper_count' => $trooper_count,
                'num_of_handlers' => $handler_count,
                'shifts' => $event->event_shifts
                    ->sortBy(EventShift::SHIFT_STARTS_AT)
                    ->map(fn ($shift) => [
                        'id' => $shift->id,
                        'starts_at' => $shift->shift_starts_at?->format('Y-m-d H:i:s'),
                        'ends_at' => $shift->shift_ends_at?->format('Y-m-d H:i:s'),
                        'display' => $shift->time_display,
                        'can_add_friend' => $trooper ? $this->shiftAllowsFriendAdd($event, $shift, $trooper) : null,
                        'can_add_guest' => $trooper ? $this->shiftAllowsGuestAdd($event, $shift, $trooper) : null,
                    ])
                    ->values(),
            ]
        ));
    }

    /**
     * action=get_roster_for_event
     * Return all sign-ups (event_troopers) for an event with costume and trooper info.
     */
    private function getRosterForEvent(Request $request): JsonResponse
    {
        $event_id = (int) $request->input('troopid');

        $event_troopers = EventTrooper::whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $event_id))
            ->with(['trooper.organizations', 'costume', 'backup_costume', 'event_shift'])
            ->orderBy(EventTrooper::SIGNED_UP_AT)
            ->get();

        $roster = $event_troopers->map(function ($et) {
            $trooper = $et->trooper;
            $organization = $trooper?->organizations->first();
            $identifier = $organization?->pivot?->identifier;

            return [
                'id' => $et->id,
                'trooperid' => $et->trooper_id,
                'troopid' => $et->event_shift->event_id,
                'shift_id' => $et->event_shift_id,
                'shift_display' => $et->event_shift->time_display,
                'status' => $et->status->value,
                'status_formatted' => $this->formatStatus($et->status),
                'costume' => $et->costume_id,
                'costume_name' => $et->costume?->name,
                'backup_costume' => $et->backup_costume_id,
                'backup_costume_name' => $et->backup_costume?->name,
                'is_handler' => $et->is_handler,
                'trooper_name' => $trooper?->display_name,
                'tkid' => $identifier,
                'tkid_formatted' => $identifier,
                'squad' => $organization?->id,
                'signuptime' => $et->signed_up_at?->format('Y-m-d H:i:s'),
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
        $event_id = (int) $request->input('troopid');
        $shift_id = (int) $request->input('shiftid', 0);

        if ($shift_id > 0)
        {
            // Exclude only troopers already signed up for this specific shift
            $signed_up_trooper_ids = EventTrooper::where(EventTrooper::EVENT_SHIFT_ID, $shift_id)
                ->where(EventTrooper::STATUS, '!=', EventTrooperStatus::CANCELLED->value)
                ->pluck(EventTrooper::TROOPER_ID);
        }
        else
        {
            $signed_up_trooper_ids = EventTrooper::whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $event_id))
                ->where(EventTrooper::STATUS, '!=', EventTrooperStatus::CANCELLED->value)
                ->pluck(EventTrooper::TROOPER_ID);
        }

        $troopers = Trooper::whereNotIn(Trooper::ID, $signed_up_trooper_ids)
            ->with('organizations')
            ->orderBy(Trooper::DISPLAY_NAME)
            ->get();

        $data = $troopers->map(function ($trooper) {
            $organization = $trooper->organizations->first();
            $identifier = $organization?->pivot?->identifier;

            return [
                'id' => $trooper->id,
                'display_name' => $trooper->display_name,
                'tkid' => $identifier,
                'tkid_formatted' => $identifier,
                'squad' => $organization?->id,
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

        $squad_id = (int) $request->input('squad');
        $open_status_values = array_map(fn ($s) => $s->value, self::OPEN_EVENT_STATUSES);

        $events = Event::whereIn(Event::STATUS, $open_status_values)
            ->where(Event::EVENT_START, '>=', now()->startOfDay())
            ->when($squad_id !== 0, fn ($q) => $q->where(Event::ORGANIZATION_ID, $squad_id))
            ->with(['event_shifts.event_troopers'])
            ->orderBy(Event::EVENT_START)
            ->get();

        $troops = $events->map(function ($event) {
            $all_troopers = $event->event_shifts->flatMap->event_troopers
                ->whereIn('status', array_map(fn ($s) => $s->value, [
                    EventTrooperStatus::GOING,
                    EventTrooperStatus::STAND_BY,
                    EventTrooperStatus::TENTATIVE,
                ]));

            $trooper_count = $all_troopers->where('is_handler', false)->count();
            $handler_count = $all_troopers->where('is_handler', true)->count();
            $troopers_allowed = $event->troopers_allowed;
            $handlers_allowed = $event->handlers_allowed;

            $notice = $this->buildNotice($event, $trooper_count, $handler_count, $troopers_allowed, $handlers_allowed);

            return array_merge($this->buildTroopObject($event), [
                'trooper_count' => $trooper_count,
                'num_of_handlers' => $handler_count,
                'notice' => $notice,
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
        $squad_id = (int) $request->input('squad');
        $organization = Organization::find($squad_id);
        $all_organizations = Organization::orderBy(Organization::NAME)->get();

        return response()->json([
            'squadName' => $organization?->name ?? '',
            'squadNames' => $all_organizations->pluck(Organization::NAME)->values(),
            'clubNames' => $all_organizations->pluck(Organization::NAME)->values(),
        ]);
    }

    /**
     * action=get_costumes_for_trooper
     * Return costumes available to a trooper.
     */
    private function getCostumesForTrooper(Request $request): JsonResponse
    {
        $user_id = (int) $request->input('trooperid');
        $friend_id = (int) $request->input('friendid', 0);

        if ($user_id > 0)
        {
            $trooper = $this->trooperFromUserId($user_id);
        }
        elseif ($friend_id > 0)
        {
            $trooper = Trooper::find($friend_id);
        }
        else
        {
            $trooper = null;
        }

        if (!$trooper)
        {
            return response()->json([]);
        }

        $organization_ids = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->pluck(TrooperOrganization::ORGANIZATION_ID)
            ->toArray();

        $costumes = Costume::forTrooper($trooper->id, $organization_ids)
            ->orderBy(Costume::NAME)
            ->get();

        $data = $costumes->map(function ($costume) {
            $organization_costume = $costume->organization_costumes->first();
            $prefix = trim((string) ($organization_costume?->prefix ?? ''));

            return [
                'id' => $costume->id,
                'name' => $costume->name,
                'abbreviation' => $prefix === '' ? '' : $prefix.' ',
                'club' => $organization_costume?->organization?->name,
            ];
        });

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
        $user_id = (int) $request->input('trooperid');
        $trooper = $this->trooperFromUserId($user_id);
        $event_id = (int) $request->input('troopid');
        $new_status = $this->resolveEventTrooperStatus($request->input('status'));
        $costume_id = (int) $request->input('costume', 0);

        if (!$trooper)
        {
            return response()->json(['error' => 'Trooper not found.'], 404);
        }

        $update_data = [EventTrooper::STATUS => $new_status->value];

        if ($costume_id > 0)
        {
            $update_data[EventTrooper::COSTUME_ID] = $costume_id;
        }

        EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $event_id))
            ->update($update_data);

        return response()->json(['success' => true]);
    }

    /**
     * action=get_confirm_events_trooper
     * Return past events where the trooper has not yet confirmed attendance.
     */
    private function getConfirmEventsTrooper(Request $request): JsonResponse
    {
        $user_id = (int) $request->input('trooperid');
        $trooper = $this->trooperFromUserId($user_id);

        if (!$trooper)
        {
            return response()->json(['troops' => []]);
        }

        $active_status_values = array_map(fn ($s) => $s->value, [
            EventTrooperStatus::GOING,
            EventTrooperStatus::STAND_BY,
            EventTrooperStatus::TENTATIVE,
        ]);

        $event_troopers = EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->whereIn(EventTrooper::STATUS, $active_status_values)
            ->with(['event_shift.event'])
            ->whereHas('event_shift.event', fn ($q) => $q
                ->where(Event::STATUS, EventStatus::CLOSED->value)
                ->where(Event::EVENT_END, '<', now())
            )
            ->get();

        $troops = $event_troopers
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
        $user_id = (int) $request->input('trooperid');
        $trooper = $this->trooperFromUserId($user_id);
        $event_id = (int) $request->input('troopid');

        if (!$trooper)
        {
            return response()->json(['inEvent' => false, 'my_shifts' => []]);
        }

        $signups = EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->where(EventTrooper::STATUS, '!=', EventTrooperStatus::CANCELLED->value)
            ->whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $event_id))
            ->with('event_shift')
            ->get();

        $my_shifts = $signups->map(fn ($et) => [
            'shift_id' => $et->event_shift_id,
            'status' => $et->status->value,
            'status_formatted' => $this->formatStatus($et->status),
        ])->values();

        return response()->json([
            'inEvent' => $signups->isNotEmpty(),
            'my_shifts' => $my_shifts,
        ]);
    }

    /**
     * action=cancel_shift
     * Cancel a trooper's sign-up for a specific shift.
     * When friendtrooperid is provided, cancels that trooper's shift instead
     * (the requester must have been the one who added them).
     */
    private function cancelShift(Request $request): JsonResponse
    {
        $user_id = (int) $request->input('trooperid');
        $requester = $this->trooperFromUserId($user_id);
        $shift_id = (int) $request->input('shiftid');
        $friend_trooper_id = (int) $request->input('friendtrooperid', 0);

        if (!$requester)
        {
            return response()->json(['error' => 'Trooper not found.'], 404);
        }

        $shift = EventShift::find($shift_id);
        if (!$shift)
        {
            return response()->json(['error' => 'Shift not found.'], 404);
        }

        if ($shift->event->status === EventStatus::MANUAL_SELECTION)
        {
            return response()->json([
                'success' => false,
                'message' => 'Manual Selection events do not allow cancellations from mobile.',
            ], 403);
        }

        if ($friend_trooper_id > 0)
        {
            // Cancelling a friend — verify the requester added them
            $record = EventTrooper::where(EventTrooper::TROOPER_ID, $friend_trooper_id)
                ->where(EventTrooper::EVENT_SHIFT_ID, $shift_id)
                ->where(EventTrooper::ADDED_BY_TROOPER_ID, $requester->id)
                ->first();

            if (!$record)
            {
                return response()->json(['error' => 'Record not found or not authorized.'], 403);
            }

            $record->update([EventTrooper::STATUS => EventTrooperStatus::CANCELLED->value]);
            $cancelled_trooper_id = $friend_trooper_id;
        }
        else
        {
            EventTrooper::where(EventTrooper::TROOPER_ID, $requester->id)
                ->where(EventTrooper::EVENT_SHIFT_ID, $shift_id)
                ->update([EventTrooper::STATUS => EventTrooperStatus::CANCELLED->value]);
            $cancelled_trooper_id = $requester->id;
        }

        EventNotification::firstOrCreate([
            EventNotification::EVENT_ID => $shift->event_id,
            EventNotification::TROOPER_ID => $cancelled_trooper_id,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * action=get_friends_for_event
     * Return troopers that the current user added to this event (still active).
     */
    private function getFriendsForEvent(Request $request): JsonResponse
    {
        $user_id = (int) $request->input('trooperid');
        $trooper = $this->trooperFromUserId($user_id);
        $event_id = (int) $request->input('troopid');

        if (!$trooper)
        {
            return response()->json([]);
        }

        $signups = EventTrooper::where(EventTrooper::ADDED_BY_TROOPER_ID, $trooper->id)
            ->where(EventTrooper::TROOPER_ID, '!=', $trooper->id)
            ->where(EventTrooper::STATUS, '!=', EventTrooperStatus::CANCELLED->value)
            ->whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $event_id))
            ->with(['trooper', 'event_shift'])
            ->get();

        $friends = $signups->map(fn ($et) => [
            'trooper_id' => $et->trooper_id,
            'trooper_name' => $et->trooper?->display_name,
            'shift_id' => $et->event_shift_id,
            'shift_display' => $et->event_shift->time_display,
            'status' => $et->status->value,
            'status_formatted' => $this->formatStatus($et->status),
        ])->values();

        return response()->json($friends);
    }

    /**
     * action=cancel_troop
     * Cancel all of a trooper's sign-ups for an event.
     */
    private function cancelTroop(Request $request): JsonResponse
    {
        $user_id = (int) $request->input('trooperid');
        $trooper = $this->trooperFromUserId($user_id);
        $event_id = (int) $request->input('troopid');

        if (!$trooper)
        {
            return response()->json(['error' => 'Trooper not found.'], 404);
        }

        $event = Event::find($event_id);
        if (!$event)
        {
            return response()->json(['error' => 'Event not found.'], 404);
        }

        if ($event->status === EventStatus::MANUAL_SELECTION)
        {
            return response()->json([
                'success' => false,
                'message' => 'Manual Selection events do not allow cancellations from mobile.',
            ], 403);
        }

        EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $event_id))
            ->update([EventTrooper::STATUS => EventTrooperStatus::CANCELLED->value]);

        // Queue notification for sign-up change
        EventNotification::firstOrCreate([
            EventNotification::EVENT_ID => $event_id,
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
        $event_id = (int) $request->input('troopid');
        $shift_id = (int) $request->input('shiftid', 0);
        $costume_id = (int) $request->input('costume', 0);
        $backup_costume_id = (int) $request->input('backupcostume', 0);
        $requested_status = $this->resolveEventTrooperStatus($request->input('status'));

        [$trooper, $added_by] = $this->resolveTrooperForSignUp($request);

        if (!$trooper)
        {
            return response()->json(['success' => false, 'success_message' => 'Trooper not found.']);
        }

        // TODO: implement isWebsiteClosed() check once that mechanism is defined.

        $event = Event::find($event_id);
        if (!$event)
        {
            return response()->json(['success' => false, 'success_message' => 'Event not found.']);
        }

        if ($event->status === EventStatus::CANCELLED)
        {
            return response()->json(['success' => false, 'success_message' => 'This event was CANCELED by Command Staff.']);
        }

        if ($event->status === EventStatus::SIGN_UP_LOCKED)
        {
            return response()->json(['success' => false, 'success_message' => 'This event was LOCKED by Command Staff.']);
        }

        $existing = $this->findExistingSignUp($trooper->id, $event_id, $shift_id);

        $effective_status = $event->status === EventStatus::MANUAL_SELECTION
            ? EventTrooperStatus::STAND_BY
            : $requested_status;

        if ($existing)
        {
            // Re-activating a cancelled friend signup still counts against the limit.
            if ($added_by && $existing->status === EventTrooperStatus::CANCELLED)
            {
                $shift_for_check = EventShift::find($existing->event_shift_id);
                if ($shift_for_check && !$shift_for_check->canSignUpTrooper($added_by))
                {
                    return response()->json(['success' => false, 'success_message' => 'You cannot add a friend to this shift.']);
                }
            }

            $existing->update([
                EventTrooper::STATUS => $effective_status->value,
                EventTrooper::COSTUME_ID => $costume_id ?: null,
                EventTrooper::BACKUP_COSTUME_ID => $backup_costume_id ?: null,
            ]);
            EventNotification::firstOrCreate([
                EventNotification::EVENT_ID => $event_id,
                EventNotification::TROOPER_ID => $trooper->id,
            ]);

            return response()->json(['success' => true, 'success_message' => 'Success!']);
        }

        $is_handler = $this->isHandlerCostume($costume_id);
        $status = $this->resolveCapacityStatus($event, $event_id, $is_handler, $effective_status);
        $shift = $this->resolveShiftForSignUp($event_id, $shift_id);

        if (!$shift)
        {
            $message = $shift_id > 0 ? 'Shift not found for this event.' : 'No shifts available for this event.';

            return response()->json(['success' => false, 'success_message' => $message]);
        }

        if ($added_by && !$shift->canSignUpTrooper($added_by))
        {
            return response()->json(['success' => false, 'success_message' => 'You cannot add a friend to this shift.']);
        }

        EventTrooper::create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::ADDED_BY_TROOPER_ID => $added_by?->id,
            EventTrooper::COSTUME_ID => $costume_id ?: null,
            EventTrooper::BACKUP_COSTUME_ID => $backup_costume_id ?: null,
            EventTrooper::STATUS => $status->value,
            EventTrooper::IS_HANDLER => $is_handler,
            EventTrooper::SIGNED_UP_AT => now(),
        ]);

        EventNotification::firstOrCreate([
            EventNotification::EVENT_ID => $event_id,
            EventNotification::TROOPER_ID => $trooper->id,
        ]);

        return response()->json(['success' => true, 'success_message' => 'Success!']);
    }

    /**
     * action=get_photos_by_event
     * Return all photos for an event, admin photos first.
     */
    private function getPhotosByEvent(Request $request): JsonResponse
    {
        $event_id = (int) $request->input('troopid');

        $uploads = EventUpload::where(EventUpload::EVENT_ID, $event_id)
            ->with('trooper')
            ->orderByDesc(EventUpload::IS_ADMINISTRATIVE)
            ->orderByDesc(EventUpload::CREATED_AT)
            ->get();

        $photos = $uploads->map(fn ($upload) => [
            'id' => $upload->id,
            'filename' => $upload->image_path_sm,
            'admin' => (int) $upload->is_administrative,
            'thumbnail_url' => $upload->small_url,
            'full_url' => $upload->large_url,
            'uploaded_by' => $upload->trooper?->display_name,
        ]);

        return response()->json(['photos' => $photos]);
    }

    /**
     * action=saveFCM
     * Save an FCM push notification token for a trooper.
     */
    private function saveFcm(Request $request): JsonResponse
    {
        $user_id = (int) $request->input('userid');
        $fcm_token = $request->input('fcm');
        $trooper = $this->trooperFromUserId($user_id);

        $exists = MobileDevice::where(MobileDevice::FCM_TOKEN, $fcm_token)->exists();

        if (!$exists)
        {
            MobileDevice::create([
                MobileDevice::TROOPER_ID => $trooper?->id,
                MobileDevice::FCM_TOKEN => $fcm_token,
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
        $fcm_token = $request->input('fcm');
        $api_key = $request->input('apiKey');

        DB::transaction(function () use ($fcm_token, $api_key) {
            MobileDevice::where(MobileDevice::FCM_TOKEN, $fcm_token)->delete();
            TrooperApiCode::where(TrooperApiCode::API_CODE, $api_key)->delete();
        });

        return response()->json(['success' => 'Records deleted!']);
    }

    /**
     * action=get_guests_for_event
     * Return guests that the current user added to this event (non-cancelled).
     */
    private function getGuestsForEvent(Request $request): JsonResponse
    {
        $user_id = (int) $request->input('trooperid');
        $trooper = $this->trooperFromUserId($user_id);
        $event_id = (int) $request->input('troopid');

        if (!$trooper)
        {
            return response()->json([]);
        }

        $guests = EventGuest::where(EventGuest::ADDED_BY_TROOPER_ID, $trooper->id)
            ->where(EventGuest::STATUS, '!=', EventGuestStatus::CANCELLED->value)
            ->whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $event_id))
            ->with('event_shift')
            ->get();

        return response()->json($guests->map(fn ($g) => [
            'id' => $g->id,
            'name' => $g->name,
            'shift_id' => $g->event_shift_id,
            'shift_display' => $g->event_shift->time_display,
            'status' => $g->status->value,
            'status_formatted' => ucfirst($g->status->value),
        ])->values());
    }

    /**
     * action=add_guest
     * Add a named guest to an event shift on behalf of a trooper.
     */
    private function addGuest(Request $request): JsonResponse
    {
        $user_id = (int) $request->input('trooperid');
        $trooper = $this->trooperFromUserId($user_id);
        $event_id = (int) $request->input('troopid');
        $shift_id = (int) $request->input('shiftid', 0);
        $name = trim((string) $request->input('name', ''));

        if (!$trooper)
        {
            return response()->json(['success' => false, 'message' => 'Trooper not found.']);
        }

        if ($name === '')
        {
            return response()->json(['success' => false, 'message' => 'Guest name is required.']);
        }

        $shift = $shift_id > 0
            ? EventShift::with(['event', 'event_troopers', 'event_guests'])
                ->where(EventShift::EVENT_ID, $event_id)
                ->find($shift_id)
            : EventShift::with(['event', 'event_troopers', 'event_guests'])
                ->where(EventShift::EVENT_ID, $event_id)
                ->orderBy(EventShift::SHIFT_STARTS_AT)
                ->first();

        if (!$shift)
        {
            return response()->json(['success' => false, 'message' => 'Shift not found.']);
        }

        if (!$shift->canSignUpGuest($trooper))
        {
            return response()->json(['success' => false, 'message' => 'You cannot add a guest to this shift.']);
        }

        EventGuest::updateOrCreate(
            [EventGuest::EVENT_SHIFT_ID => $shift->id, EventGuest::NAME => $name],
            [
                EventGuest::ADDED_BY_TROOPER_ID => $trooper->id,
                EventGuest::STATUS => $shift->event->status === EventStatus::MANUAL_SELECTION
                    ? EventGuestStatus::STAND_BY->value
                    : EventGuestStatus::GOING->value,
                EventGuest::SIGNED_UP_AT => now(),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Guest added!']);
    }

    /**
     * action=cancel_guest
     * Cancel a guest signup (must be added by the requesting trooper).
     */
    private function cancelGuest(Request $request): JsonResponse
    {
        $user_id = (int) $request->input('trooperid');
        $trooper = $this->trooperFromUserId($user_id);
        $guest_id = (int) $request->input('guestid');

        if (!$trooper)
        {
            return response()->json(['success' => false, 'message' => 'Trooper not found.']);
        }

        $guest = EventGuest::where(EventGuest::ID, $guest_id)
            ->where(EventGuest::ADDED_BY_TROOPER_ID, $trooper->id)
            ->with('event_shift.event')
            ->first();

        if (!$guest)
        {
            return response()->json(['success' => false, 'message' => 'Guest not found or not authorized.']);
        }

        if ($guest->event_shift->event->status === EventStatus::MANUAL_SELECTION)
        {
            return response()->json([
                'success' => false,
                'message' => 'Manual Selection events do not allow cancellations from mobile.',
            ], 403);
        }

        $guest->update([EventGuest::STATUS => EventGuestStatus::CANCELLED->value]);

        return response()->json(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Check whether a trooper can still add a friend to the given shift.
     * Uses the already-loaded event_troopers collection — no extra queries.
     */
    private function shiftAllowsFriendAdd(Event $event, EventShift $shift, Trooper $trooper): bool
    {
        $shift->setRelation('event', $event);

        return $shift->canSignUpTrooper($trooper);
    }

    /**
     * Check whether a trooper can still add a guest to the given shift.
     */
    private function shiftAllowsGuestAdd(Event $event, EventShift $shift, Trooper $trooper): bool
    {
        $shift->setRelation('event', $event);

        return $shift->canSignUpGuest($trooper);
    }

    /**
     * Build a standard troop/event object for list responses.
     */
    private function buildTroopObject(Event $event): array
    {
        return [
            'troopid' => $event->id,
            'name' => $event->name,
            'dateStart' => $event->event_start,
            'dateEnd' => $event->event_end,
            'location' => $event->venue,
            'thread_id' => $event->thread_id,
            'post_id' => $event->post_id,
            'squad' => $event->organization_id,
        ];
    }

    /**
     * Build a notice string for an event based on capacity.
     */
    private function buildNotice(Event $event, int $trooper_count, int $handler_count, ?int $troopers_allowed, ?int $handlers_allowed): string
    {
        if ($event->status === EventStatus::SIGN_UP_LOCKED)
        {
            return 'THIS TROOP IS FULL!';
        }

        if ($trooper_count <= 1)
        {
            return 'NOT ENOUGH TROOPERS FOR THIS EVENT!';
        }

        $troopers_full = $troopers_allowed !== null && $trooper_count >= $troopers_allowed;
        $handlers_full = $handlers_allowed !== null && $handler_count >= $handlers_allowed;

        if ($troopers_full && ($handlers_allowed === null || $handlers_full))
        {
            return 'THIS TROOP IS FULL!';
        }

        return '';
    }

    /**
     * Return a human-readable label for an EventTrooperStatus.
     */
    private function formatStatus(EventTrooperStatus $status): string
    {
        return match ($status)
        {
            EventTrooperStatus::GOING => 'Going',
            EventTrooperStatus::STAND_BY => 'Stand By',
            EventTrooperStatus::TENTATIVE => 'Tentative',
            EventTrooperStatus::ATTENDED => 'Attended',
            EventTrooperStatus::CANCELLED => 'Cancelled',
            EventTrooperStatus::PENDING => 'Pending',
            EventTrooperStatus::NOT_PICKED => 'Not Picked',
            EventTrooperStatus::NO_SHOW => 'No Show',
            EventTrooperStatus::UNABLE_TO_ATTEND => 'Unable to Attend',
            default => 'Unknown',
        };
    }

    /**
     * Resolve a request status string to an EventTrooperStatus.
     */
    private function resolveEventTrooperStatus(mixed $raw_status): EventTrooperStatus
    {
        if ($raw_status instanceof EventTrooperStatus)
        {
            return $raw_status;
        }

        $status = strtolower(trim((string) $raw_status));

        return match ($status)
        {
            EventTrooperStatus::GOING->value => EventTrooperStatus::GOING,
            EventTrooperStatus::STAND_BY->value, 'standby', 'stand_by' => EventTrooperStatus::STAND_BY,
            EventTrooperStatus::TENTATIVE->value => EventTrooperStatus::TENTATIVE,
            EventTrooperStatus::ATTENDED->value => EventTrooperStatus::ATTENDED,
            EventTrooperStatus::NO_SHOW->value, 'no_show' => EventTrooperStatus::NO_SHOW,
            EventTrooperStatus::PENDING->value => EventTrooperStatus::PENDING,
            EventTrooperStatus::CANCELLED->value => EventTrooperStatus::CANCELLED,
            EventTrooperStatus::NOT_PICKED->value, 'not_picked' => EventTrooperStatus::NOT_PICKED,
            EventTrooperStatus::UNABLE_TO_ATTEND->value, 'unable_to_attend' => EventTrooperStatus::UNABLE_TO_ATTEND,
            default => throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid backing value for enum %s',
                (string) $raw_status,
                EventTrooperStatus::class,
            )),
        };
    }

    /**
     * Resolve the trooper and added_by for a sign-up request.
     *
     * @return array{0: ?Trooper, 1: ?Trooper}
     */
    private function resolveTrooperForSignUp(Request $request): array
    {
        $added_by_id = (int) $request->input('addedby', 0);

        if ($added_by_id > 0)
        {
            $trooper = Trooper::find((int) $request->input('trooperid'));
            $added_by = $this->trooperFromUserId($added_by_id);
        }
        else
        {
            $trooper = $this->trooperFromUserId((int) $request->input('trooperid'));
            $added_by = null;
        }

        return [$trooper, $added_by];
    }

    /**
     * Find an existing sign-up record for a trooper on an event/shift.
     */
    private function findExistingSignUp(int $trooper_id, int $event_id, int $shift_id): ?EventTrooper
    {
        if ($shift_id > 0)
        {
            return EventTrooper::where(EventTrooper::TROOPER_ID, $trooper_id)
                ->where(EventTrooper::EVENT_SHIFT_ID, $shift_id)
                ->first();
        }

        return EventTrooper::where(EventTrooper::TROOPER_ID, $trooper_id)
            ->whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $event_id))
            ->first();
    }

    /**
     * Determine whether a costume is a handler costume by name.
     */
    private function isHandlerCostume(int $costume_id): bool
    {
        if (!$costume_id)
        {
            return false;
        }

        $costume = Costume::find($costume_id);

        return $costume !== null && Str::contains(strtolower($costume->name), 'handler');
    }

    /**
     * Resolve the effective sign-up status, downgrading to stand-by if the event is at capacity.
     */
    private function resolveCapacityStatus(Event $event, int $event_id, bool $is_handler, EventTrooperStatus $requested_status): EventTrooperStatus
    {
        if ($requested_status === EventTrooperStatus::CANCELLED)
        {
            return $requested_status;
        }

        $active_values = array_map(fn ($s) => $s->value, [
            EventTrooperStatus::GOING,
            EventTrooperStatus::STAND_BY,
            EventTrooperStatus::TENTATIVE,
        ]);

        if ($is_handler)
        {
            $handlers_allowed = $event->handlers_allowed;

            if ($handlers_allowed !== null)
            {
                $handler_count = EventTrooper::whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $event_id))
                    ->where(EventTrooper::IS_HANDLER, true)
                    ->whereIn(EventTrooper::STATUS, $active_values)
                    ->count();

                if ($handler_count >= $handlers_allowed)
                {
                    return EventTrooperStatus::STAND_BY;
                }
            }
        }
        else
        {
            $troopers_allowed = $event->troopers_allowed;

            if ($troopers_allowed !== null)
            {
                $trooper_count = EventTrooper::whereHas('event_shift', fn ($q) => $q->where(EventShift::EVENT_ID, $event_id))
                    ->where(EventTrooper::IS_HANDLER, false)
                    ->whereIn(EventTrooper::STATUS, $active_values)
                    ->count();

                if ($trooper_count >= $troopers_allowed)
                {
                    return EventTrooperStatus::STAND_BY;
                }
            }
        }

        return $requested_status;
    }

    /**
     * Find the requested shift, or fall back to the first available shift on the event.
     */
    private function resolveShiftForSignUp(int $event_id, int $shift_id): ?EventShift
    {
        if ($shift_id > 0)
        {
            return EventShift::where(EventShift::EVENT_ID, $event_id)->find($shift_id);
        }

        return EventShift::where(EventShift::EVENT_ID, $event_id)
            ->orderBy(EventShift::SHIFT_STARTS_AT)
            ->first();
    }

    /**
     * Count active (going/stand-by/tentative) attendees for an event.
     *
     * @return array{0: int, 1: int} [$trooper_count, $handler_count]
     */
    private function countActiveAttendees(Event $event): array
    {
        $active_troopers = $event->event_shifts->flatMap->event_troopers
            ->whereIn('status', array_map(fn ($s) => $s->value, [
                EventTrooperStatus::GOING,
                EventTrooperStatus::STAND_BY,
                EventTrooperStatus::TENTATIVE,
            ]));

        return [
            $active_troopers->count(),
            $active_troopers->where('is_handler', true)->count(),
        ];
    }

    /**
     * Build a formatted venue location string from event address fields.
     */
    private function buildEventLocation(Event $event): string
    {
        return collect([
            $event->venue_address,
            $event->venue_city,
            $event->venue_state,
            $event->venue_zip,
            $event->venue_country,
        ])->filter()->implode(', ');
    }

    /**
     * Build the capacity message string for the event detail response.
     */
    private function buildCapacityMessage(?int $troopers_allowed, ?int $handlers_allowed, int $trooper_count, int $handler_count): string
    {
        if ($troopers_allowed === null && $handlers_allowed === null)
        {
            return '';
        }

        $message = '';

        if ($troopers_allowed !== null)
        {
            $remaining = max(0, $troopers_allowed - $trooper_count);
            $message .= "This event is limited to {$troopers_allowed} troopers. {$remaining} troopers remaining.\n";
        }

        if ($handlers_allowed !== null)
        {
            $remaining = max(0, $handlers_allowed - $handler_count);
            $message .= "This event is limited to {$handlers_allowed} handlers. {$remaining} handlers remaining.\n";
        }

        return $message;
    }
}

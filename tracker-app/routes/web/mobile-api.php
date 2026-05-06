<?php

declare(strict_types=1);

use App\Http\Controllers\Api\MobileApiController;
use App\Models\MobileDevice;
use App\Models\PushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/mobile-api', MobileApiController::class)->name('api.mobile');

Route::post('/fcm/register', function (Request $request) {
    $token = $request->input('token');
    if (!$token || !Auth::check()) {
        return response()->json(['ok' => false]);
    }
    MobileDevice::updateOrCreate(
        [MobileDevice::FCM_TOKEN => $token],
        [MobileDevice::TROOPER_ID => Auth::id()],
    );
    return response()->json(['ok' => true]);
})->middleware('auth')->name('fcm.register');

// FCM-token-authenticated endpoints for the native Flutter notifications screen.
$fcmTrooper = function (Request $request): ?int {
    $token = $request->header('FCM-Token');
    if (!$token) return null;
    return MobileDevice::where(MobileDevice::FCM_TOKEN, $token)
        ->value(MobileDevice::TROOPER_ID);
};

Route::get('/api/push-notifications', function (Request $request) use ($fcmTrooper): JsonResponse {
    $trooperId = $fcmTrooper($request);
    if (!$trooperId) return response()->json([], 401);

    $notifications = PushNotification::where(PushNotification::TROOPER_ID, $trooperId)
        ->latest()
        ->get()
        ->map(fn($n) => [
            'id'         => $n->id,
            'title'      => $n->title,
            'body'       => $n->body,
            'url'        => $n->url,
            'read_at'    => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at->toIso8601String(),
        ]);

    return response()->json($notifications);
})->name('api.push-notifications.index');

Route::post('/api/push-notifications/{id}/read', function (Request $request, int $id) use ($fcmTrooper): JsonResponse {
    $trooperId = $fcmTrooper($request);
    if (!$trooperId) return response()->json([], 401);

    PushNotification::where(PushNotification::ID, $id)
        ->where(PushNotification::TROOPER_ID, $trooperId)
        ->whereNull(PushNotification::READ_AT)
        ->update([PushNotification::READ_AT => now()]);

    return response()->json(['ok' => true]);
})->name('api.push-notifications.read');

Route::delete('/api/push-notifications', function (Request $request) use ($fcmTrooper): JsonResponse {
    $trooperId = $fcmTrooper($request);
    if (!$trooperId) return response()->json([], 401);

    PushNotification::where(PushNotification::TROOPER_ID, $trooperId)->delete();

    return response()->json(['ok' => true]);
})->name('api.push-notifications.clear');

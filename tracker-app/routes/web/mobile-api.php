<?php

declare(strict_types=1);

use App\Models\MobileDevice;
use App\Models\Trooper;
use App\Models\TrooperNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::post('/fcm/register', function (Request $request) {
    $validated = $request->validate(['token' => 'required|string']);
    MobileDevice::updateOrCreate(
        [MobileDevice::FCM_TOKEN  => $validated['token']],
        [MobileDevice::TROOPER_ID => Auth::id()],
    );
    return response()->json(['ok' => true]);
})->middleware('auth')->name('fcm.register');

// FCM-token-authenticated endpoints for the Flutter notifications screen.
$fcmTrooper = function (Request $request): ?int {
    $token = $request->header('FCM-Token');
    if (!$token) return null;
    return MobileDevice::where(MobileDevice::FCM_TOKEN, $token)
        ->value(MobileDevice::TROOPER_ID);
};

Route::get('/api/push-notifications', function (Request $request) use ($fcmTrooper): JsonResponse {
    $trooperId = $fcmTrooper($request);
    if (!$trooperId) return response()->json([], 401);

    $trooper = Trooper::find($trooperId);
    if (!$trooper) return response()->json([], 401);

    $notifications = $trooper->notifications()->latest()->get()
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

Route::post('/api/push-notifications/{id}/read', function (Request $request, string $id) use ($fcmTrooper): JsonResponse {
    $trooperId = $fcmTrooper($request);
    if (!$trooperId) return response()->json([], 401);

    $notification = TrooperNotification::find($id);
    if (!$notification || (int) $notification->notifiable_id !== $trooperId) {
        return response()->json([], 404);
    }

    $notification->markAsRead();

    return response()->json(['ok' => true]);
})->name('api.push-notifications.read');

Route::delete('/api/push-notifications', function (Request $request) use ($fcmTrooper): JsonResponse {
    $trooperId = $fcmTrooper($request);
    if (!$trooperId) return response()->json([], 401);

    $trooper = Trooper::find($trooperId);
    if (!$trooper) return response()->json([], 401);

    $trooper->notifications()->delete();

    return response()->json(['ok' => true]);
})->name('api.push-notifications.clear');

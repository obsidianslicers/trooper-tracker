<?php

declare(strict_types=1);

use App\Http\Controllers\Api\MobileApiController;
use App\Models\MobileDevice;
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

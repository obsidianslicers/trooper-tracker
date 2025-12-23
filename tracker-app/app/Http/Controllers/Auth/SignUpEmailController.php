<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SignUpEmailController extends Controller
{
    public function __invoke(Request $request)
    {
        $registration_auth = [
            'method' => 'email',
            'email' => null, // will be filled in on the form
            'expires_at' => now()->addMinutes(20),
        ];

        Session::put('registration_auth', $registration_auth);

        return redirect()->route('auth.register');
    }
}
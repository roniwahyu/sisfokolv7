<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('username', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        LoginLog::create([
            'user_id' => $user->id,
            'user_code' => $user->display_code,
            'user_name' => $user->display_name,
            'position' => $user->getRoleNames()->first(),
            'job_title' => $user->getRoleNames()->first(),
            'ip_address' => $request->ip(),
            'logged_in_at' => now(),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

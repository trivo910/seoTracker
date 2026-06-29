<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $attempted = Auth::attempt($credentials, $request->boolean('remember'));

        // Log the attempt regardless of outcome
        LoginLog::create([
            'user_id'        => $attempted ? Auth::id() : null,
            'email'          => $request->email,
            'status'         => $attempted ? 'success' : 'failed',
            'failure_reason' => $attempted ? null : 'Invalid credentials',
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        if (! $attempted) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $user = Auth::user();

        // Block inactive accounts
        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated. Contact an administrator.',
            ]);
        }

        // Update last login timestamp
        $user->recordLogin($request->ip(), $request->userAgent());

        $request->session()->regenerate();

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

<?php

namespace App\Http\Middleware;

use App\Models\LoginLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogLoginActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only intercept POST to the login route
        if ($request->isMethod('post') && $request->routeIs('login')) {
            $user = Auth::user();

            LoginLog::create([
                'user_id'        => $user?->id,
                'email'          => $request->input('email', ''),
                'status'         => $user ? 'success' : 'failed',
                'failure_reason' => $user ? null : 'Invalid credentials',
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ]);

            // Update last_login fields on the user
            if ($user) {
                $user->recordLogin($request->ip(), $request->userAgent());
            }
        }

        return $response;
    }
}

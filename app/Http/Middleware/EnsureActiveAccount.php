<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && ! $request->user()->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => User::DEACTIVATED_MESSAGE, 'deactivated' => true, 'csrf_token' => csrf_token()], 401);
            }

            return redirect('/')->with('login_error', User::DEACTIVATED_MESSAGE);
        }

        return $next($request);
    }
}

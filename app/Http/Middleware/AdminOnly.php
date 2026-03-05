<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login.show');
        }

        // Block non-admin + inactive (termasuk cashier)
        if (($user->role ?? null) !== 'admin' || ($user->is_active ?? false) !== true) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}

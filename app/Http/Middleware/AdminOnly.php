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

        // Audit: Cek apakah user ada (Data Check)
        if (!$user) {
            return redirect()->route('login.show');
        }

        // Audit: Validasi Role & Status Aktif
        // Jika bukan admin ATAU tidak aktif, eksekusi pembersihan session
        if ($user->role !== 'admin' || $user->is_active !== true) {
            
            Auth::logout();
            
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Menggunakan abort 403 adalah praktik terbaik untuk akses terlarang
            abort(403, 'Akses ditolak: Akun tidak memiliki otoritas admin atau non-aktif.');
        }

        return $next($request);
    }
}

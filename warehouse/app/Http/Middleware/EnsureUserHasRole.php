<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // If no specific roles are required or user has matching role/admin privilege
        if (empty($roles) || $user->hasRole($roles)) {
            return $next($request);
        }

        abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Master / Admin always passes
        if ($user->isMaster() || in_array(strtolower($user->username ?? ''), ['master', 'admin'])) {
            return $next($request);
        }

        // If no permissions specified or user has any of the required permissions
        if (empty($permissions) || $user->hasPermission($permissions)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses Ditolak: Akun role (' . ($user->role ?? 'User') . ') Anda tidak memiliki hak akses untuk tindakan ini.'
            ], 403);
        }

        abort(403, 'Akses Ditolak: Akun role (' . ($user->role ?? 'User') . ') Anda tidak memiliki izin untuk mengakses halaman ini. Hubungi Administrator jika membutuhkan akses.');
    }
}

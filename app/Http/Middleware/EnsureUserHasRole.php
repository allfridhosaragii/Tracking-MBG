<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('login'));
        }

        $userRoleValue = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        // Check against passed role parameters
        $allowed = false;
        foreach ($roles as $role) {
            // Support comma-separated roles in a single param as well
            $split = explode(',', $role);
            foreach ($split as $r) {
                if (trim($r) === $userRoleValue) {
                    $allowed = true;
                    break 2;
                }
            }
        }

        if (! $allowed) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akses ditolak. Peran Anda tidak memiliki wewenang untuk sumber daya ini.',
                    'current_role' => $userRoleValue,
                ], 403);
            }

            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk membuka modul ini.');
        }

        return $next($request);
    }
}

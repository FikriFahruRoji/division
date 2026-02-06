<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if (!$request->user()->isActive()) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Akun Anda tidak aktif.');
        }

        if (!empty($roles)) {
            $userRole = $request->user()->role;
            
            // Super admin has access to all admin routes
            if ($userRole === 'super_admin' && in_array('admin', $roles)) {
                return $next($request);
            }
            
            if (!in_array($userRole, $roles)) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }
        }

        return $next($request);
    }
}

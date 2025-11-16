<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Verificar si el usuario tiene acceso al admin
        if (!$user->canAccessAdmin()) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        // Si se especifican roles específicos, verificar que el usuario tenga uno de ellos
        if (!empty($roles)) {
            if (!in_array($user->role, $roles)) {
                abort(403, 'No tienes permisos para acceder a esta sección.');
            }
        }

        return $next($request);
    }
}


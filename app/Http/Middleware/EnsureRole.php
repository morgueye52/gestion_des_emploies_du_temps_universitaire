<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && $user->is_active && $user->hasRole($roles), 403);
        abort_if($user->hasRole('department_manager') && ! $user->department_id, 403, 'Votre compte n’est associé à aucun département.');

        return $next($request);
    }
}

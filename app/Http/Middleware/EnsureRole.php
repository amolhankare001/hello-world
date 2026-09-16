<?php

namespace App\Http\Middleware;

use App\Enums\RoleCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowedRoles = array_map(
            static fn (string $role): RoleCode => RoleCode::from($role),
            $roles,
        );

        abort_unless($request->user()?->hasRole(...$allowedRoles), 403);

        return $next($request);
    }
}

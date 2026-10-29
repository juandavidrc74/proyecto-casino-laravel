<?php

namespace App\Http\Middleware;

use App\Enums\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $rolesPermitidos = array_map(static fn (string $rol) => Rol::tryFrom($rol), $roles);
        $rolesPermitidos = array_filter($rolesPermitidos);

        abort_unless($request->user()?->tieneRol(...$rolesPermitidos), 403);

        return $next($request);
    }
}

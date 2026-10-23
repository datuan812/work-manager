<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->parent_access_pin_enabled) {
            return $next($request);
        }

        abort_unless($request->session()->get('parent_access_granted') === true, 403);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     * Usage: ->middleware('role:admin')
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if ($request->user()->role !== $role) {
            abort(403);
        }

        return $next($request);
    }
}

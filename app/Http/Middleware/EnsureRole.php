<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'You are not authorized to access this section.');
        }

        if (!$user->hasRole(...$roles)) {
            // Graceful role redirect instead of hard 403 for valid authenticated users.
            if ($user->isParent() && Route::has('parent.dashboard')) {
                return redirect()->route('parent.dashboard')
                    ->with('status', 'Switched to parent dashboard based on your account role.');
            }

            if (Route::has('dashboard')) {
                return redirect()->route('dashboard')
                    ->with('status', 'Switched to admin dashboard based on your account role.');
            }

            abort(403, 'You are not authorized to access this section.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanAccessProcess
{
    public function handle(Request $request, Closure $next, string $processName): Response
    {
        $user = $request->user();

        if (in_array($user->role, [UserRole::Manager, UserRole::LeaderAdmin], true)) {
            return $next($request);
        }

        abort_unless(
            $user->role === UserRole::Checker && $user->process?->name === $processName,
            403,
            "You're not assigned to the {$processName} process."
        );

        return $next($request);
    }
}

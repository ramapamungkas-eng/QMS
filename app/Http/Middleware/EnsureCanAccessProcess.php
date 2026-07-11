<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanAccessProcess
{
    public function handle(Request $request, Closure $next, string $processName): Response
    {
        /** @var User $user */
        $user = $request->user();

        if (in_array($user->role, [UserRole::Manager, UserRole::LeaderAdmin], true)) {
            return $next($request);
        }

        abort_unless(
            $user->load('process')->process?->name === $processName,
            403,
            "You're not assigned to the {$processName} process."
        );

        return $next($request);
    }
}

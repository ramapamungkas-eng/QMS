<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless(
            in_array($user->role, [UserRole::Manager, UserRole::LeaderAdmin], true),
            403
        );

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\EmployeeAccountGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeeAccountEligible
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            EmployeeAccountGate::assertUserCanAuthenticate($user);
        }

        return $next($request);
    }
}

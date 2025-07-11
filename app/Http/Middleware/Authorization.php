<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class Authorization
{
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user || !$user->hasPermission($permission)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}

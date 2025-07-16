<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

class Authentication
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            // if (!$user || !$user->is_active) {
            //     return response()->json(['error' => 'Unauthorized or inactive user'], 403);
            // }

            // Check if user has a valid session in the DB
            $hasSession = DB::table('sessions')
                ->where('user_id', $user->id)
                ->exists();

            if (!$hasSession) {
                JWTAuth::invalidate(JWTAuth::getToken());
                return response()->json(['error' => 'Session expired or not found'], 401);
            }

            // Attach user to request
            $request->merge(['auth_user' => $user]);

        } catch (Exception $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}

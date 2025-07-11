<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\PermissionRole;
use App\Models\Auth\UserPermission;
use App\Models\Auth\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
        $user = JWTAuth::user();

        DB::table('sessions')->insert([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
        ]);
    }

    public function logout()
    {
        $user = JWTAuth::user();
        JWTAuth::invalidate(JWTAuth::getToken());
        DB::table('sessions')->where('user_id', $user->id)->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function me()
    {
        $user = JWTAuth::parseToken()->authenticate();

        //::::::::::::::::::::::::::::::::::::: GET USER ROLE IDs
        $roleIds = UserRole::where('user_id', $user->id)->pluck('role_id');

        //::::::::::::::::::::::::::::::::::::: GET PERMISSIONS FROM ROLES
        $rolePermissionNames = PermissionRole::whereIn('role_id', $roleIds)
            ->with('permission:id,name')
            ->get()
            ->pluck('permission.name');

        //::::::::::::::::::::::::::::::::::::: GET DIRECT USER PERMISSIONS
        $userPermissionNames = UserPermission::where('user_id', $user->id)
            ->with('permission:id,name')
            ->get()
            ->pluck('permission.name');

        //::::::::::::::::::::::::::::::::::::: MERGE AND REMOVE DUPLICATES
        $allPermissions = $rolePermissionNames
            ->merge($userPermissionNames)
            ->unique()
            ->values(); // reset keys

        return response()->json([
            'user' => $user,
            'permissions' => $allPermissions,
        ], 200);
    }


    public function logoutUser(User $user)
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
        return response()->json(['message' => 'Successfully logged out']);
    }
}

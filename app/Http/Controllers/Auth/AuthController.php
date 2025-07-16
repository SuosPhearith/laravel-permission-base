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
        $roleIds = UserRole::where('user_id', $user->id)
            ->whereHas('role', function ($query) {
                $query->where('is_active', true)
                    ->whereNull('deleted_at');
            })
            ->pluck('role_id');

        //::::::::::::::::::::::::::::::::::::: GET PERMISSIONS FROM ROLES
        $rolePermissionNames = PermissionRole::whereIn('role_id', $roleIds)
            ->whereHas('permission', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('module', function ($q) {
                        $q->where('is_active', true);
                    });
            })
            ->with(['permission' => function ($query) {
                $query->select('id', 'name')
                    ->where('is_active', true)
                    ->whereHas('module', function ($q) {
                        $q->where('is_active', true);
                    });
            }])
            ->get()
            ->pluck('permission.name');

        //::::::::::::::::::::::::::::::::::::: GET DIRECT USER PERMISSIONS
        $userPermissionNames = UserPermission::where('user_id', $user->id)
            ->whereHas('permission', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('module', function ($q) {
                        $q->where('is_active', true);
                    });
            })
            ->with(['permission' => function ($query) {
                $query->select('id', 'name')
                    ->where('is_active', true)
                    ->whereHas('module', function ($q) {
                        $q->where('is_active', true);
                    });
            }])
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
            'navigator' => [
                [
                    'title' => 'Home',
                    'to' => ['name' => 'root'],
                    'icon' => ['icon' => 'tabler-smart-home'],
                ],
                [
                    'title' => 'Product',
                    'to' => ['name' => 'products'],
                    'icon' => ['icon' => 'tabler-file'],
                ],
                [
                    'title' => 'User',
                    'to' => ['name' => 'users'],
                    'icon' => ['icon' => 'tabler-user-circle'],
                ],
                [
                    'title' => 'Setting',
                    'icon' => ['icon' => 'tabler-settings'],
                    'children' => [
                        ['title' => "Role", 'to' => "settings-role"],
                        ['title' => "Permission", 'to' => "settings-permission"],
                        ['title' => "Config", 'to' => "settings-config"],
                    ],
                ],
            ]
        ], 200);
    }
}

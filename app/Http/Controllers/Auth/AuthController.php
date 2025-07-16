<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\PermissionRole;
use App\Models\Auth\UserPermission;
use App\Models\Auth\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string', // email or phone
            'password' => 'required|string',
        ]);

        $login = $request->input('login');
        $password = $request->input('password');

        // Find user by email or phone_number
        $user = User::where('email', $login)
            ->orWhere('phone_number', $login)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $token = JWTAuth::fromUser($user);

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

        //::::::::::::::::::::::::::::::::::::: BUILD NAVIGATOR BASED ON PERMISSION
        $navigator = [];

        if ($allPermissions->contains('view-home')) {
            $navigator[] = [
                'title' => 'Home',
                'to' => ['name' => 'root'],
                'icon' => ['icon' => 'tabler-smart-home'],
            ];
        }

        if ($allPermissions->contains('view-product')) {
            $navigator[] = [
                'title' => 'Product',
                'to' => ['name' => 'products'],
                'icon' => ['icon' => 'tabler-file'],
            ];
        }

        if ($allPermissions->contains('view-users')) {
            $navigator[] = [
                'title' => 'User',
                'to' => ['name' => 'users'],
                'icon' => ['icon' => 'tabler-user-circle'],
            ];
        }

        if ($allPermissions->contains('view-setting')) {
            $children = [];

            if ($allPermissions->contains('view-role-setting')) {
                $children[] = ['title' => "Role", 'to' => "settings-role"];
            }

            if ($allPermissions->contains('view-module-setting')) {
                $children[] = ['title' => "Permission", 'to' => "settings-permission"];
            }

            if ($allPermissions->contains('view-config-setting')) {
                $children[] = ['title' => "Config", 'to' => "settings-config"];
            }

            // Only push if there are children
            if (!empty($children)) {
                $navigator[] = [
                    'title' => 'Setting',
                    'icon' => ['icon' => 'tabler-settings'],
                    'children' => $children,
                ];
            }
        }

        return response()->json([
            'user' => $user,
            'permissions' => $allPermissions,
            'navigator' => $navigator
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([
                'name'         => 'required|string|min:1|max:100',
                'avatar'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'email'        => [
                    'required',
                    'string',
                    'email',
                    'max:100',
                    Rule::unique('users')->ignore($user->id),
                ],
                'phone_number' => [
                    'required',
                    'string',
                    'max:12',
                    Rule::unique('users')->ignore($user->id),
                ],
            ]);

            $updateData = [
                'name'         => $validated['name'],
                'email'        => $validated['email'],
                'phone_number' => $validated['phone_number'],
            ];

            //:::::::::::::::::::::::::::::::::::: HANDLE AVATAR
            if ($request->hasFile('avatar')) {
                // Upload new image
                $path = $request->file('avatar')->store('avatar', 'public');

                // Delete old image if exists
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }

                $updateData['avatar'] = $path;
            }

            //:::::::::::::::::::::::::::::::::::: UPDATE PROFILE
            $user->update($updateData);

            return response()->json(['message' => 'Updated successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'error' => 'Failed to update',
            ], 500);
        }
    }
}

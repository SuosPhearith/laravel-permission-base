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
// :::::::::::::::::::::::::::::::::::::::::::::: 2FA IMPORT
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $request->input('login');
        $password = $request->input('password');

        $user = User::where('email', $login)
            ->orWhere('phone_number', $login)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        if ($user->enable_2fa) {
            $randomKey = Str::random(32);
            $expiresAt = now()->addMinutes(10)->timestamp;
            $key = "{$randomKey}-{$expiresAt}";
            $user->two_factor_key = $key;
            $user->save();
        }

        // 2FA not enabled, issue token immediately
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
            'verify' => $user->enable_2fa ? true : false,
            'access_token' => $token,
            'token_type' => 'bearer',
            'two_factor_key' => $key ?? null,
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
                'icon' => ['icon' => 'tabler-users'],
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

        if ($allPermissions->contains('view-users')) {
            $navigator[] = [
                'title' => 'Account',
                'to' => ['name' => 'account'],
                'icon' => ['icon' => 'tabler-user-circle'],
            ];
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

    public function changePassowrd(Request $request)
    {
        try {
            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([]);

            $user = JWTAuth::parseToken()->authenticate();

            DB::beginTransaction();


            DB::commit();

            return response()->json(['message' => 'Updated successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'error' => 'Failed to create permissions'
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            $user = JWTAuth::parseToken()->authenticate();

            //:::::::::::::::::::::::::::::::::::: CHECK CURRENT PASSWORD
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json(['error' => 'Current password is incorrect'], 403);
            }

            DB::beginTransaction();

            //:::::::::::::::::::::::::::::::::::: UPDATE PASSWORD
            $user->password = Hash::make($validated['new_password']);
            $user->save();

            DB::commit();

            return response()->json(['message' => 'Password updated successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'error' => 'Failed to update password'
            ], 500);
        }
    }



    //::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: 2FA WITH GOOGLE

    public function setup2FA()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $google2fa = new Google2FA();

        // Generate and store secret
        $secret = $google2fa->generateSecretKey();
        $user->google2fa_secret = $secret;
        $user->enable_2fa = true;
        $user->save();

        // Generate QR code URL
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'PHARMACY - CALMETTE',
            $user->email,
            $secret
        );

        DB::table('sessions')->where('user_id', $user->id)->delete();

        return response()->json([
            'otpauth_url' => $qrCodeUrl
        ]);
    }

    public function verify2FA(Request $request)
    {
        $request->validate([
            'two_factor_key' => 'required|string',
            'otp' => 'required|string',
        ]);

        // Find user by key
        $user = User::where('two_factor_key', $request->two_factor_key)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired 2FA key'], 403);
        }

        $google2fa = new Google2FA();

        if (!$google2fa->verifyKey($user->google2fa_secret, $request->otp)) {
            return response()->json(['message' => 'Invalid OTP'], 422);
        }

        [$actualKey, $timestamp] = explode('-', $request->two_factor_key);

        if (now()->timestamp > (int) $timestamp) {
            return response()->json(['message' => '2FA key has expired'], 401);
        }

        // Transaction: clear key, save user, create session
        try {
            DB::beginTransaction();

            $user->two_factor_key = null;
            $user->save();

            $token = JWTAuth::fromUser($user);

            DB::table('sessions')->insert([
                'id' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload' => '',
                'last_activity' => now()->timestamp,
            ]);

            DB::commit();

            return response()->json([
                'message' => '2FA verified successfully',
                'access_token' => $token,
                'token_type' => 'bearer',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Something went wrong during 2FA verification'], 500);
        }
    }
}

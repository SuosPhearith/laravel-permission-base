<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\Module;
use App\Models\Auth\Permission;
use App\Models\Auth\PermissionRole;
use App\Models\Auth\Role;
use App\Models\Auth\UserPermission;
use App\Models\Auth\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FAQRCode\Google2FA;

class UserController extends Controller
{
    public function index(Request $request)
    {
        //:::::::::::::::::::::::::::::::::::::::::: GET FILTER
        $validated = $request->validate([
            'per_page'          => 'integer|min:1|max:100',
            'keyword'           => 'nullable|string|max:255',
            'sort_direction'    => 'in:asc,desc',
            'role_id'           => 'nullable|integer|min:1|exists:roles,id',
            'is_active'         => 'nullable|in:0,1',
        ]);

        //:::::::::::::::::::::::::::::::::::::::::: VALIDATE FILTER
        $perPage        = $validated['per_page'] ?? 10;
        $keyword        = $validated['keyword'] ?? null;
        $sortDirection  = $validated['sort_direction'] ?? 'desc';
        $roleId         = $validated['role_id'] ?? null;
        $isActive       = $validated['is_active'] ?? null;

        //:::::::::::::::::::::::::::::::::::::::::: QUERY
        $usersQuery = User::query();

        //:::::::::::::::::::::::::::::::::::::::::: SEARCH
        if ($keyword) {
            $usersQuery->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        //:::::::::::::::::::::::::::::::::::::::::: FILTER
        if ($roleId) {
            $usersQuery->whereHas('roles', function ($query) use ($roleId) {
                $query->where('roles.id', $roleId);
            });
        }


        if (!is_null($isActive)) {
            $usersQuery->where('is_active', $isActive);
        }


        //:::::::::::::::::::::::::::::::::::::::::: SORT
        $usersQuery
            ->with(['roles:id,name'])
            ->orderBy('created_at', $sortDirection);

        //:::::::::::::::::::::::::::::::::::::::::: PAGINATION
        $users = $usersQuery->paginate($perPage);

        //:::::::::::::::::::::::::::::::::::::::::: RESPONSE
        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ], 200);
    }

    public function getUserById(User $user)
    {
        $user->load('roles:id');

        $data = $user->toArray();
        $data['roles'] = $user->roles->pluck('id')->toArray();

        return response()->json([
            'data' => $data,
        ], 200);
    }

    public function deleteUser(Request $request, User $user)
    {
        try {
            $user->delete();
            return response()->json(['message' => 'Deleted successfully']);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'errors' => $e->errors()
                ],
                422
            );
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(
                [
                    'error' => 'Failed to create'
                ],
                500
            );
        }
    }

    public function resetPassword(Request $request, User $user)
    {
        try {
            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([
                'new_password'              => 'required|string|min:6|max:30',
            ]);

            //:::::::::::::::::::::::::::::::::::: UPDATE
            $user->update([
                'password'          => Hash::make($validated['new_password']),
            ]);

            return response()->json(['message' => 'Updated successfully']);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'errors' => $e->errors()
                ],
                422
            );
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(
                [
                    'error' => 'Failed to create'
                ],
                500
            );
        }
    }


    public function createUser(Request $request)
    {
        try {
            DB::beginTransaction();

            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([
                'name'              => 'required|string|min:3|max:100',
                'email'             => 'required|email|unique:users,email',
                'phone_number'      => 'required|string|min:9|max:12',
                'password'          => 'required|string|min:6|max:30',
                'role_id'           => 'required|array|min:1',
                'role_id.*'         => 'integer|exists:roles,id',
            ]);

            //:::::::::::::::::::::::::::::::::::: CREATE USER
            $newUser = User::create([
                'name'              => $validated['name'],
                'email'             => $validated['email'],
                'phone_number'      => $validated['phone_number'],
                'password'          => Hash::make($validated['password']),
            ]);

            //:::::::::::::::::::::::::::::::::::: ASSIGN ROLES TO USER
            foreach ($validated['role_id'] as $roleId) {
                UserRole::create([
                    'user_id' => $newUser->id,
                    'role_id' => $roleId,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Created successfully'], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'error' => 'Failed to create' . $e
            ], 500);
        }
    }

    public function editUser(Request $request, User $user)
    {
        try {
            DB::beginTransaction();

            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([
                'name'          => 'required|string|min:3|max:100',
                'email'         => 'required|email|unique:users,email,' . $user->id . ',id',
                'phone_number'  => 'required|string|min:9|max:12|unique:users,phone_number,' . $user->id . ',id',
                'role_id'       => 'nullable|array',
                'role_id.*'     => 'integer|exists:roles,id',
            ]);

            //:::::::::::::::::::::::::::::::::::: UPDATE USER
            $user->name         = $validated['name'];
            $user->email        = $validated['email'];
            $user->phone_number = $validated['phone_number'];
            $user->save();

            //:::::::::::::::::::::::::::::::::::: SYNC ROLES
            UserRole::where('user_id', $user->id)->delete();

            if (!empty($validated['role_id'])) {
                foreach ($validated['role_id'] as $roleId) {
                    UserRole::create([
                        'user_id' => $user->id,
                        'role_id' => $roleId,
                    ]);
                }
            }

            DB::commit();

            return response()->json(['message' => 'User updated successfully'], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'error' => 'Failed to update user'
            ], 500);
        }
    }


    public function toggleStatus(User $user)
    {
        try {

            //:::::::::::::::::::::::::::::::::::: update
            $user->update([
                'is_active'  => !$user->is_active
            ]);
            return response()->json(['message' => 'Updated successfully']);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'errors' => $e->errors()
                ],
                422
            );
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(
                [
                    'error' => 'Failed to create'
                ],
                500
            );
        }
    }

    public function logoutUser(User $user)
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function addNewPermission(User $user, Permission $permission)
    {
        try {

            //:::::::::::::::::::::::::::::::::::: update
            UserPermission::create([
                'user_id' => $user->id,
                'permission_id' => $user->id,
            ]);
            return response()->json(['message' => 'Created successfully']);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'errors' => $e->errors()
                ],
                422
            );
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(
                [
                    'error' => 'Failed to create'
                ],
                500
            );
        }
    }

    public function updateUserPermission(Request $request, User $user)
    {
        try {
            DB::beginTransaction();

            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([
                'permission_id'     => 'nullable|array',
                'permission_id.*'   => 'integer|exists:permissions,id',
            ]);

            //:::::::::::::::::::::::::::::::::::: DELETE OLD PERMISSIONS
            UserPermission::where('user_id', $user->id)->delete();

            //:::::::::::::::::::::::::::::::::::: INSERT NEW PERMISSIONS
            foreach ($validated['permission_id'] as $permissionId) {
                UserPermission::create([
                    'user_id' => $user->id,
                    'permission_id' => $permissionId,
                ]);
            }

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
                'error' => 'Failed to update'
            ], 500);
        }
    }

    public function getUserPermission(User $user)
    {
        //::::::::::::::::::::::::::::::::::::: Get user direct permission IDs
        $userPermissions = UserPermission::where('user_id', $user->id)->pluck('permission_id');

        //::::::::::::::::::::::::::::::::::::: Get user role permission IDs
        $userRoleIds = UserRole::where('user_id', $user->id)->pluck('role_id');
        $rolePermissionIds = PermissionRole::whereIn('role_id', $userRoleIds)
            ->pluck('permission_id')
            ->unique();

        //::::::::::::::::::::::::::::::::::::: Load all active modules with their permissions
        $modules = Module::where('is_active', true)
            ->with(['permissions' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        //::::::::::::::::::::::::::::::::::::: Map and group permissions under each module
        $grouped = $modules->map(function ($module) use ($rolePermissionIds) {
            return [
                'module' => $module->name,
                'permissions' => $module->permissions->map(function ($permission) use ($rolePermissionIds) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'disabled' => $rolePermissionIds->contains($permission->id),
                    ];
                }),
            ];
        });

        return response()->json([
            'data' => [
                'permissions' => $grouped,
                'user_permissions' => $userPermissions,
            ]
        ], 200);
    }

    public function enable2FA(User $user)
    {
        $google2fa = new Google2FA();

        // Generate and store secret
        $secret = $google2fa->generateSecretKey();
        $user->google2fa_secret = $secret;
        $user->enable_2fa = true;
        $user->save();

        // Delete all sessions
        DB::table('sessions')->where('user_id', $user->id)->delete();

        // Generate QR code URL
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'PHARMACY - CALMETTE',
            $user->email,
            $secret
        );

        return response()->json([
            'otpauth_url' => $qrCodeUrl
        ]);
    }

    public function disable2FA(User $user)
    {
        $user->google2fa_secret = '';
        $user->enable_2fa = false;
        $user->save();
        return response()->json([
            'message' => 'Updated Successfully'
        ]);
    }
}

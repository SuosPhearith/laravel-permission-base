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
use Illuminate\Validation\ValidationException;

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
            $usersQuery->where('role_id', $roleId);
        }

        if ($isActive) {
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

    public function createUser(Request $request)
    {
        try {
            DB::beginTransaction();

            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([
                'name'      => 'required|string|min:3|max:100',
                'email'     => 'required|email|unique:users,email',
                'password'  => 'required|string|min:6|max:30',
                'role_id'   => 'required|array|min:1',
                'role_id.*' => 'integer|exists:roles,id',
            ]);

            //:::::::::::::::::::::::::::::::::::: CREATE USER
            $newUser = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
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
                'error' => 'Failed to create'
            ], 500);
        }
    }

    public function editUser(Request $request, User $user)
    {
        try {
            DB::beginTransaction();

            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([
                'name'      => 'required|string|min:3|max:100',
                'email'     => 'required|email|unique:users,email,' . $user->id . ',id',
                'role_id'   => 'required|array|min:1',
                'role_id.*' => 'integer|exists:roles,id',
            ]);

            //:::::::::::::::::::::::::::::::::::: UPDATE USER
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->save();

            //:::::::::::::::::::::::::::::::::::: SYNC ROLES
            UserRole::where('user_id', $user->id)->delete();
            foreach ($validated['role_id'] as $roleId) {
                UserRole::create([
                    'user_id' => $user->id,
                    'role_id' => $roleId,
                ]);
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
}

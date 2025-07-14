<?php

namespace App\Http\Controllers;

use App\Models\Auth\Permission;
use App\Models\Auth\PermissionRole;
use App\Models\Auth\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SampleController extends Controller
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
            ->with(['role:id,name'])
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

    public function createMany(Request $request)
    {
        try {

            DB::beginTransaction();

            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([
                'name'              => 'required|string|min:1|max:100',
                'permission_id'     => 'required|array|min:1',
                'permission_id.*'   => 'integer|exists:permissions,id',
            ]);

            //:::::::::::::::::::::::::::::::::::: CREATE ROLE
            $newRole = Role::create([
                'name'          => $validated['name'],
            ]);

            //:::::::::::::::::::::::::::::::::::: CREATE ROLE PERMISSION
            foreach ($validated['permission_id'] as $permissionId) {
                PermissionRole::create([
                    'permission_id'     => $permissionId,
                    'role_id'           => $newRole->id,
                ]);
            }

            DB::commit();

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
            DB::rollBack();
            Log::error($e);
            return response()->json(
                [
                    'error' => 'Failed to create'
                ],
                500
            );
        }
    }

    public function createOne(Request $request)
    {
        try {
            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([
                'name'              => 'required|string|min:1|max:100',
                'permission_id'     => 'required|array|min:1',
                'permission_id.*'   => 'integer|exists:permissions,id',
            ]);

            //:::::::::::::::::::::::::::::::::::: CREATE ROLE
            $newRole = Role::create([
                'name'          => $validated['name'],
            ]);

            //:::::::::::::::::::::::::::::::::::: CREATE ROLE PERMISSION
            foreach ($validated['permission_id'] as $permissionId) {
                PermissionRole::create([
                    'permission_id'     => $permissionId,
                    'role_id'           => $newRole->id,
                ]);
            }
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

    public function getById(User $user)
    {
        return response()->json([
            'data' => $user
        ], 200);
    }
}

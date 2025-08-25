<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Auth\Module;
use App\Models\Auth\Permission;
use App\Models\Auth\PermissionRole;
use App\Models\Auth\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    public function getRoles(Request $request)
    {
        //:::::::::::::::::::::::::::::::::::::::::: GET FILTER
        $validated = $request->validate([
            'with'          => 'nullable|string|in:module,permission',
        ]);

        //:::::::::::::::::::::::::::::::::::::::::: VALIDATE FILTER
        $with   =  $validated['with'] ?? null;

        if ($with === 'module') {
            $data = Role::with([
                'permissions:id,name,module_id',
                'permissions.module:id,name'
            ])
                ->select(['id', 'name'])
                ->get();
            return response()->json([
                'data' => $data,
            ], 200);
        } else if ($with === 'permission') {
            $data = Role::with(['permissions:id,name'])
                ->select(['id', 'name'])
                ->get();
            return response()->json([
                'data' => $data,
            ], 200);
        } else {
            $data = Role::select(['id', 'name'])
                ->get();
            return response()->json([
                'data' => $data
            ], 200);
        }
    }

    public function getRoleWithPermission()
    {
        $roles = Role::with(['permissions:id'])->select(['id', 'name'])->get();

        $data = $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permission_ids' => $role->permissions->pluck('id')->toArray(),
            ];
        });

        return response()->json(['data' => $data], 200);
    }

    public function createPermission(Request $request, Module $module)
    {
        try {
            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([
                'permissions' => 'required|array|min:1',
                'permissions.*.name' => 'required|string|min:1|max:100',
            ]);

            DB::beginTransaction();

            foreach ($validated['permissions'] as $perm) {
                Permission::create([
                    'name' => $perm['name'],
                    'module_id' => $module->id,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Permissions created successfully']);
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

    public function createModule(Request $request)
    {
        try {
            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([
                'name'              => 'required|string|min:1|max:150',
            ]);

            //:::::::::::::::::::::::::::::::::::: CREATE
            Module::create([
                'name'          => $validated['name'],
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

    public function listRole(Request $request)
    {
        //:::::::::::::::::::::::::::::::::::::::::: GET FILTER
        $validated = $request->validate([
            'per_page'          => 'integer|min:1|max:100',
            'keyword'           => 'nullable|string|max:255',
            'sort_direction'    => 'in:asc,desc',
            'is_active'         => 'nullable|in:0,1',
            'with'              => 'nullable|string|in:module,permission',
        ]);

        //:::::::::::::::::::::::::::::::::::::::::: VALIDATE FILTER
        $perPage        = $validated['per_page'] ?? 10;
        $keyword        = $validated['keyword'] ?? null;
        $sortDirection  = $validated['sort_direction'] ?? 'desc';
        $isActive       = $validated['is_active'] ?? null;
        $with           = $validated['with'] ?? null;

        //:::::::::::::::::::::::::::::::::::::::::: QUERY
        $rolesQuery = Role::query();

        //:::::::::::::::::::::::::::::::::::::::::: EAGER LOADING
        if ($with === 'module') {
            $rolesQuery->with([
                'permissions:id,name,module_id',
                'permissions.module:id,name'
            ])->select(['id', 'name', 'is_active', 'created_at']);
        } elseif ($with === 'permission') {
            $rolesQuery->with(['permissions:id,name'])
                ->select(['id', 'name', 'is_active', 'created_at']);
        } else {
            $rolesQuery->select(['id', 'name', 'is_active', 'created_at']);
        }

        //:::::::::::::::::::::::::::::::::::::::::: SEARCH
        if ($keyword) {
            $rolesQuery->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%");
            });
        }

        //:::::::::::::::::::::::::::::::::::::::::: FILTER
        if (!is_null($isActive)) {
            $rolesQuery->where('is_active', $isActive);
        }

        //:::::::::::::::::::::::::::::::::::::::::: SORT
        $rolesQuery->orderBy('created_at', $sortDirection);

        //:::::::::::::::::::::::::::::::::::::::::: PAGINATION
        $roles = $rolesQuery->paginate($perPage);

        //:::::::::::::::::::::::::::::::::::::::::: RESPONSE
        return response()->json([
            'data' => $roles->items(),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
                'last_page' => $roles->lastPage(),
            ],
        ], 200);
    }

    public function createRole(Request $request)
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
                    'error' => 'Failed to create' . $e
                ],
                500
            );
        }
    }

    public function deleteRole(Role $role)
    {
        try {

            DB::beginTransaction();
            $role->delete();

            DB::commit();

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

    public function toggleRole(Role $role)
    {
        try {

            //:::::::::::::::::::::::::::::::::::: update
            $role->update([
                'is_active'  => !$role->is_active
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

    public function updateRole(Request $request, Role $role)
    {
        try {
            DB::beginTransaction();

            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([
                'name'              => 'required|string|min:1|max:100',
                'permission_id'     => 'required|array|min:1',
                'permission_id.*'   => 'integer|exists:permissions,id',
            ]);

            //:::::::::::::::::::::::::::::::::::: UPDATE ROLE NAME
            $role->update([
                'name' => $validated['name'],
            ]);

            //:::::::::::::::::::::::::::::::::::: SYNC PERMISSIONS
            // Remove old and attach new permissions
            $role->permissions()->sync($validated['permission_id']);

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


    public function getRoleById(Role $role)
    {
        $role->load('permissions');

        return response()->json([
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_active' => $role->is_active,
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
                'permission_ids' => $role->permissions->pluck('id'),
            ]
        ], 200);
    }


    public function listPermission()
    {
        $permissions =  Permission::with(['module:id,name'])
            ->select(['id', 'name', 'is_active', 'module_id'])
            ->get();

        return response()->json([
            'data' => $permissions
        ], 200);
    }

    public function togglePermission(Permission $permission)
    {
        try {

            //:::::::::::::::::::::::::::::::::::: update
            $permission->update([
                'is_active'  => !$permission->is_active
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

    public function deletePermission(Permission $permission)
    {
        try {

            //:::::::::::::::::::::::::::::::::::: update
            $permission->delete();
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

    public function listModule()
    {
        $modules = Module::select(['id', 'name', 'is_active'])
            ->with(['permissions:id,name,is_active,module_id'])
            ->orderBy('id', 'desc') // ✅ Correct query builder usage
            ->get();

        return response()->json([
            'data' => $modules
        ], 200);
    }


    public function toggleModule(Module $module)
    {
        try {

            //:::::::::::::::::::::::::::::::::::: update
            $module->update([
                'is_active'  => !$module->is_active
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

    public function deleteModule(Module $module)
    {
        try {
            //:::::::::::::::::::::::::::::::::::: delete all related permissions
            $module->permissions()->delete();

            //:::::::::::::::::::::::::::::::::::: delete the module
            $module->delete();

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
                    'error' => 'Failed to delete'
                ],
                500
            );
        }
    }


    public function setup(Request $request)
    {
        //:::::::::::::::::::::::::::::::::::: GET
        $selects = explode(',', $request->query('select', ''));

        $data = [];

        //:::::::::::::::::::::::::::::::::::: CONDITIONAL
        if (in_array('role', $selects)) {
            $data['roles'] = Role::select(['id', 'name'])->get();
        }

        //:::::::::::::::::::::::::::::::::::: CONDITIONAL
        if (in_array('module', $selects)) {
            $data['modules'] = Module::select(['id', 'name'])->get();
        }

        return response()->json([
            'data' => $data
        ], 200);
    }
}

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
    public function listRole(Request $request)
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
                    'error' => 'Failed to create'
                ],
                500
            );
        }
    }

    public function deleteRole(Request $request)
    {
        try {

            DB::beginTransaction();

            //:::::::::::::::::::::::::::::::::::: VALIDATE
            $validated = $request->validate([
                'role_id'             => 'required|int|exists:roles,id',
            ]);

            //:::::::::::::::::::::::::::::::::::: DELETE ROLE
            $role = Role::findOrFail($request->role_id);

            //:::::::::::::::::::::::::::::::::::: DELETE ROLE PERMISSION
            PermissionRole::where('role_id', $role->id)->delete();

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

    public function listPermission()
    {
        $permissions =  Permission::with(['module:id,name'])
            ->select(['id', 'name', 'module_id'])
            ->get();

        return response()->json([
            'data' => $permissions
        ], 200);
    }

    public function listModule()
    {
        $modules = Module::select(['id', 'name'])
            ->with(['permissions:id,name,module_id'])
            ->get();

        return response()->json([
            'data' => $modules
        ], 200);
    }
}

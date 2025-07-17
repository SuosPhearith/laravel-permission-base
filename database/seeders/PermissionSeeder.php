<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: Seed Modules
        $userModuleId = DB::table('modules')->insertGetId(
            [
                'id'            => 1,
                'name'          => 'User Management',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ]
        );

        $settingModuleId = DB::table('modules')->insertGetId(
            [
                'id'            => 2,
                'name'          => 'Setting Management',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ]
        );

        $homeModuleId = DB::table('modules')->insertGetId(
            [
                'id'            => 3,
                'name'          => 'Home Management',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ]
        );

        $productModuleId = DB::table('modules')->insertGetId(
            [
                'id'            => 4,
                'name'          => 'Product Management',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ]
        );

        //::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: Seed Permissions
        $permissions = [
            ['name' => 'view-users',                'module_id' => $userModuleId], // GET /, /{user}
            ['name' => 'create-users',              'module_id' => $userModuleId], // POST /
            ['name' => 'edit-users',                'module_id' => $userModuleId], // PUT /{user}
            ['name' => 'delete-users',              'module_id' => $userModuleId], // DELETE /{user}
            ['name' => 'view-session-users',        'module_id' => $userModuleId], // GET /{user}/get-user-permission
            ['name' => 'logout-users',              'module_id' => $userModuleId], // DELETE /{user}/logout
            ['name' => 'reset-password-users',      'module_id' => $userModuleId], // PUT /{user}/reset-password
            ['name' => 'ban-users',                 'module_id' => $userModuleId], // PUT /{user}/toggle-status

            ['name' => 'grant-permission-users',    'module_id' => $userModuleId], // POST /{user}/{permission}/add-permission
            ['name' => 'update-permission-users',   'module_id' => $userModuleId], // PUT /{user}/update-permission

            ['name' => 'enable-2fa-users',          'module_id' => $userModuleId], // PUT /{user}/enable-2fa
            ['name' => 'disable-2fa-users',         'module_id' => $userModuleId], // PUT /{user}/disable-2fa

            ['name' => 'view-setting', 'module_id' => $settingModuleId],                      // SETTING ::: MAIN

            // ROLE SETTING
            ['name' => 'view-role-setting', 'module_id' => $settingModuleId],                // listRole ::: MAIN
            ['name' => 'create-role-setting', 'module_id' => $settingModuleId],              // createRole
            ['name' => 'update-role-setting', 'module_id' => $settingModuleId],              // updateRole
            ['name' => 'delete-role-setting', 'module_id' => $settingModuleId],              // deleteRole
            ['name' => 'toggle-role-setting', 'module_id' => $settingModuleId],              // toggleRole
            ['name' => 'view-role-permission-setting', 'module_id' => $settingModuleId],     // getRoleWithPermission
            ['name' => 'get-role-by-id-setting', 'module_id' => $settingModuleId],           // getRoleById

            // PERMISSION SETTING
            ['name' => 'view-permission-setting', 'module_id' => $settingModuleId],          // listPermission ::: MAIN
            ['name' => 'toggle-permission-setting', 'module_id' => $settingModuleId],        // togglePermission
            ['name' => 'create-permission-setting', 'module_id' => $settingModuleId],        // createPermission

            // MODULE SETTING
            ['name' => 'view-module-setting', 'module_id' => $settingModuleId],              // listModule ::: MAIN
            ['name' => 'create-module-setting', 'module_id' => $settingModuleId],            // createModule
            ['name' => 'toggle-module-setting', 'module_id' => $settingModuleId],            // toggleModule

            // SETUP
            ['name' => 'view-config-setting', 'module_id' => $settingModuleId], // ::: MAIN

            ['name' => 'view-home',                         'module_id' => $homeModuleId],      // HOME ::: MAIN

            ['name' => 'view-product',                      'module_id' => $productModuleId],   // PRODUCT ::: MAIN
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert(
                [
                    'name'          => $permission['name'],
                    'module_id'     => $permission['module_id'],
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now(),
                ]
            );
        }

        $roleId = DB::table('roles')->insertGetId(
            [
                'id'            => 1,
                'name'          => 'Admin',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
        );

        $permissionIds = DB::table('permissions')
            ->pluck('id')
            ->toArray();

        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->insert([
                'permission_id'     => $permissionId,
                'role_id'           => $roleId,
            ]);
        }
    }
}

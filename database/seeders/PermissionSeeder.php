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
            ['name' => 'view-users',                        'module_id' => $userModuleId],      // USER ::: MAIN
            ['name' => 'create-users',                      'module_id' => $userModuleId],      // USER
            ['name' => 'edit-users',                        'module_id' => $userModuleId],      // USER
            ['name' => 'delete-users',                      'module_id' => $userModuleId],      // USER
            ['name' => 'view-session-users',                'module_id' => $userModuleId],      // USER
            ['name' => 'logout-users',                      'module_id' => $userModuleId],      // USER
            ['name' => 'reset-password-users',              'module_id' => $userModuleId],      // USER
            ['name' => 'ban-users',                         'module_id' => $userModuleId],      // USER

            ['name' => 'view-setting',                      'module_id' => $settingModuleId],   // SETTING ::: MAIN

            ['name' => 'view-role-setting',                 'module_id' => $settingModuleId],   // ROLE SETTING ::: MAIN
            ['name' => 'view-module-setting',               'module_id' => $settingModuleId],   // MODULE SETTING ::: MAIN
            ['name' => 'view-config-setting',               'module_id' => $settingModuleId],   // CONFIG SETTING ::: MAIN

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

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

        $exchangeModuleId = DB::table('modules')->insertGetId(
            [
                'id'            => 4,
                'name'          => 'Exchange Management',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ]
        );

        //::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: Seed Permissions
        $permissions = [
            ['name' => 'view-home',                     'module_id' => $homeModuleId],          // HOME ::: MAIN

            ['name' => 'view-exchange',                 'module_id' => $exchangeModuleId],      // EXCHANGE ::: MAIN

            ['name' => 'view-users',                    'module_id' => $userModuleId],          // SETTING ::: MAIN
            ['name' => 'create-users',                  'module_id' => $userModuleId],
            ['name' => 'edit-users',                    'module_id' => $userModuleId],
            ['name' => 'delete-users',                  'module_id' => $userModuleId],
            ['name' => 'view-session-users',            'module_id' => $userModuleId],
            ['name' => 'logout-users',                  'module_id' => $userModuleId],
            ['name' => 'reset-password-users',          'module_id' => $userModuleId],
            ['name' => 'ban-users',                     'module_id' => $userModuleId],

            ['name' => 'grant-permission-users',        'module_id' => $userModuleId],
            ['name' => 'update-permission-users',       'module_id' => $userModuleId],

            ['name' => 'enable-2fa-users',              'module_id' => $userModuleId],
            ['name' => 'disable-2fa-users',             'module_id' => $userModuleId],

            ['name' => 'view-setting', 'module_id' => $settingModuleId],                        // SETTING ::: MAIN

            // ROLE SETTING
            ['name' => 'view-role-setting',             'module_id' => $settingModuleId],       // ::: MAIN
            ['name' => 'create-role-setting',           'module_id' => $settingModuleId],
            ['name' => 'update-role-setting',           'module_id' => $settingModuleId],
            ['name' => 'delete-role-setting',           'module_id' => $settingModuleId],
            ['name' => 'toggle-role-setting',           'module_id' => $settingModuleId],
            ['name' => 'view-role-permission-setting',  'module_id' => $settingModuleId],
            ['name' => 'get-role-by-id-setting',        'module_id' => $settingModuleId],
            // PERMISSION SETTING
            ['name' => 'view-permission-setting',       'module_id' => $settingModuleId],       //  ::: MAIN
            ['name' => 'toggle-permission-setting',     'module_id' => $settingModuleId],
            ['name' => 'delete-permission-setting',     'module_id' => $settingModuleId],
            ['name' => 'create-permission-setting',     'module_id' => $settingModuleId],

            // MODULE SETTING
            ['name' => 'view-module-setting',           'module_id' => $settingModuleId],       // ::: MAIN
            ['name' => 'create-module-setting',         'module_id' => $settingModuleId],
            ['name' => 'toggle-module-setting',         'module_id' => $settingModuleId],
            ['name' => 'delete-module-setting',         'module_id' => $settingModuleId],

            // SETUP
            ['name' => 'view-config-setting',           'module_id' => $settingModuleId],       // ::: MAIN
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

        DB::table('roles')->insert([
            [
                'id'         => 1,
                'name'       => 'Admin',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id'         => 2,
                'name'       => 'Guest',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);


        $permissionIds = DB::table('permissions')
            ->pluck('id')
            ->toArray();

        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->insert([
                'permission_id'     => $permissionId,
                'role_id'           => 1,
            ]);
        }

        DB::table('permission_role')->insert([
                'permission_id'     => 1,
                'role_id'           => 2,
            ]);
    }
}

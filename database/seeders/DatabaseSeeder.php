<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            ConfigSeeder::class,
            ExchangeRateSeeder::class,
        ]);

        $user = User::factory()->create([
            // 'id'                => 1,
            'name'              => 'SUOS PHEARITH',
            'email'             => 'suosphearith@gmail.com',
            'phone_number'      => '069265958',
            'is_active'         => 1,
            'password'          => Hash::make('123456'),
        ]);

        DB::table('user_role')->insert(
            [
                'role_id'   => 1,
                'user_id'   => $user->id,
            ],
        );
    }
}

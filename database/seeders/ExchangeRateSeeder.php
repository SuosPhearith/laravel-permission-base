<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('exchange_rate')->insertGetId(
            [
                'id'            => 1,
                'rate'          => 4000,
                'source'        => 'Default',
                'is_active'     => true,
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ]
        );
    }
}

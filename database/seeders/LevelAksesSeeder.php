<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LevelAksesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('level_akses')->insert([
            [
                'nama_level' => 'Admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_level' => 'Users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

    }
}

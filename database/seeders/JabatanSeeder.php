<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JabatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('jabatan')->insert([
            [
                'nama_jabatan' => 'Wadir I',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_jabatan' => 'Wadir II',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_jabatan' => 'Wadir III',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

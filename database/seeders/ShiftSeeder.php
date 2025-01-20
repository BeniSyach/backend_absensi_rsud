<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('shifts')->insert([
            ['nama_shift' => '1 Shift', 'created_at' => now(), 'updated_at' => now()],
            ['nama_shift' => '3 Shift', 'created_at' => now(), 'updated_at' => now()],
            ['nama_shift' => 'Pramusaji', 'created_at' => now(), 'updated_at' => now()],
            ['nama_shift' => 'Pranata Jamuan', 'created_at' => now(), 'updated_at' => now()],
            ['nama_shift' => 'Dokter 2 Shift', 'created_at' => now(), 'updated_at' => now()],
            ['nama_shift' => 'Dokter Spesialis', 'created_at' => now(), 'updated_at' => now()],
            ['nama_shift' => 'Dokter IGD', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

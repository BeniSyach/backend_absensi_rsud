<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DivisiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('divisi')->insert([
            [
                'nama_divisi' => 'Bag. Kesekretariatan',
                'id_atasan' => null, 
                'id_jabatan' => null, 
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_divisi' => 'Bag. Tata Usaha',
                'id_atasan' => null, 
                'id_jabatan' => null, 
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_divisi' => 'Bag. Humas',
                'id_atasan' => null, 
                'id_jabatan' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

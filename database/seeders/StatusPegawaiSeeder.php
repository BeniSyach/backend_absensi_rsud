<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusPegawaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('status_pegawai')->insert([
            [
                'nama_status' => 'ASN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_status' => 'Non ASN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

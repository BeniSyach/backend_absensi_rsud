<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(StatusPegawaiSeeder::class);
        $this->call(GenderSeeder::class);
        $this->call(LevelAksesSeeder::class);
        $this->call(DivisiSeeder::class);
        $this->call(ShiftSeeder::class);
        $this->call(JabatanSeeder::class);
    }
}

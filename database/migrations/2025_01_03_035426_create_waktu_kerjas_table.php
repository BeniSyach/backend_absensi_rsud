<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('waktu_kerjas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hari_id')->constrained('haris')->onDelete('cascade'); // Relasi ke tabel hari
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade'); // Relasi ke tabel shift
            $table->time('jam_mulai');   // Jam mulai kerja
            $table->time('jam_selesai'); // Jam selesai kerja
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waktu_kerjas');
    }
};

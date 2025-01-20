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
        Schema::create('spt', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->date('tanggal_spt');       // Tanggal SPT
            $table->time('waktu_spt');         // Waktu SPT
            $table->integer('lama_acara');     // Lama acara dalam menit
            $table->string('lokasi_spt', 255); // Lokasi SPT
            $table->string('file_spt')->nullable(); // File SPT (path file)
            $table->timestamps();

            // $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spt');
    }
};

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
        Schema::create('absen_masuk', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->datetime('waktu_masuk');
            $table->unsignedBigInteger('shift_id');
            $table->unsignedBigInteger('waktu_kerja_id');
            $table->string('longitude');
            $table->string('latitude');
            $table->time('selish');
            $table->string('photo');
            $table->string('tpp_in');
            $table->string('keterangan');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
            $table->foreign('waktu_kerja_id')->references('id')->on('waktu_kerjas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absen_masuk');
    }
};

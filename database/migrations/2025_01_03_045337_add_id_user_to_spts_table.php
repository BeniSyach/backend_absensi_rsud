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
        Schema::table('spt', function (Blueprint $table) {
            // $table->unsignedBigInteger('id_user')->after('id'); // Kolom id_user setelah id
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spt', function (Blueprint $table) {
            $table->dropForeign(['id_user']); // Hapus foreign key
            $table->dropColumn('id_user');   // Hapus kolom id_user
        });
    }
};

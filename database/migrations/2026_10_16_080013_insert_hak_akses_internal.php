<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Masukkan hak akses 'internal' jika belum ada
        DB::table('hak_akses')->insertOrIgnore([
            'hak' => 'internal'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Menghapus hak akses 'internal' jika di-rollback
        DB::table('hak_akses')->where('hak', 'internal')->delete();
    }
};
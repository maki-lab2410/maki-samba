<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->text('value')->nullable();
                $table->timestamps();
            });

            // Nilai bawaan instansi (dapat diubah via Web Admin Pengaturan atau .env)
            $now = now();
            DB::table('settings')->insert([
                ['key' => 'app_title', 'value' => 'Maki Samba', 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'institution_name', 'value' => 'Laboratorium Rekam Medis & Informasi Kesehatan (RMIK)', 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'institution_short', 'value' => 'Lab RMIK', 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'app_description', 'value' => 'Portal File Manager & Otomatisasi Samba', 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

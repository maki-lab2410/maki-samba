<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'semester')) {
                    $table->unsignedTinyInteger('semester')->nullable()->default(1);
                }
                if (!Schema::hasColumn('users', 'kelas')) {
                    $table->string('kelas', 50)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'semester')) {
                    $table->dropColumn('semester');
                }
                if (Schema::hasColumn('users', 'kelas')) {
                    $table->dropColumn('kelas');
                }
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('nim')->unique();
                $table->string('name');
                $table->string('password');
                $table->string('role')->default('mahasiswa'); // admin, laboran, dosen, mahasiswa
                $table->unsignedTinyInteger('semester')->nullable()->default(1);
                $table->string('kelas', 50)->nullable();
                $table->unsignedBigInteger('quota_limit')->default(1073741824); // Default 1 GB
                $table->unsignedBigInteger('quota_used')->default(0);
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opd', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('singkatan', 50)->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('operator')->after('password');
            $table->foreignId('opd_id')->nullable()->after('role')->constrained('opd')->nullOnDelete();
            $table->boolean('aktif')->default(true)->after('opd_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opd_id');
            $table->dropColumn(['role', 'aktif']);
        });

        Schema::dropIfExists('opd');
    }
};

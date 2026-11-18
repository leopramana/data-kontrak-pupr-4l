<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datasets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_id')->constrained('opd')->restrictOnDelete();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();

            // Metadata (K8)
            $table->string('periode', 100)->nullable();
            $table->string('satuan', 100)->nullable();
            $table->string('frekuensi', 50)->nullable();
            $table->string('sumber')->nullable();

            // Alur pemeriksaan (K3)
            $table->string('status', 20)->default('draf')->index();
            $table->text('catatan_walidata')->nullable();
            $table->json('hasil_pemindaian')->nullable();

            $table->string('nama_file_asli')->nullable();
            $table->unsignedInteger('jumlah_baris')->default(0);

            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamp('diajukan_pada')->nullable();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disetujui_pada')->nullable();
            $table->timestamp('data_diperbarui_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('dataset_kolom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained('datasets')->cascadeOnDelete();
            $table->string('nama');
            $table->unsignedSmallInteger('urutan');
            $table->string('tipe', 10)->default('teks'); // teks | angka
            $table->unique(['dataset_id', 'urutan']);
        });

        Schema::create('dataset_baris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained('datasets')->cascadeOnDelete();
            $table->unsignedInteger('urutan');
            $table->json('isi'); // array nilai, urutannya sama dengan dataset_kolom.urutan
            $table->index(['dataset_id', 'urutan']);
        });

        // Riwayat (K9)
        Schema::create('dataset_riwayat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained('datasets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 30);
            $table->text('catatan')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dataset_riwayat');
        Schema::dropIfExists('dataset_baris');
        Schema::dropIfExists('dataset_kolom');
        Schema::dropIfExists('datasets');
    }
};

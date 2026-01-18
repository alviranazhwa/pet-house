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
        Schema::create('jenis_layanan', function (Blueprint $table) {
            $table->id();

            // Kode layanan: GRM-0001
            $table->string('kode_layanan')->unique();

            // Nama layanan: Grooming Kecil, Grooming Besar, dll
            $table->string('nama_layanan');

            // Tarif jasa
            $table->decimal('tarif', 15, 2);

            // Keterangan tambahan (opsional)
            $table->text('keterangan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_layanan');
    }
};

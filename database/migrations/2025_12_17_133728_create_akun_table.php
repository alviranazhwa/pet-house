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
       Schema::create('akun', function (Blueprint $table) {
            $table->id();
            $table->string('kode_akun', 10)->unique();
            $table->string('nama_akun');
            $table->enum('kategori', [
                'aset', 'kewajiban', 'modal', 'pendapatan', 'beban'
            ]);
            $table->enum('posisi_saldo', ['debit', 'kredit']);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akun');
    }
};

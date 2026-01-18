<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id();

            $table->string('kode_produk')->unique();
            $table->string('nama_produk');

            $table->foreignId('kategori_produk_id')
                ->constrained('kategori_produk')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('satuan')->default('pcs');
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);

            $table->boolean('is_aktif')->default(true);
            $table->text('keterangan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};

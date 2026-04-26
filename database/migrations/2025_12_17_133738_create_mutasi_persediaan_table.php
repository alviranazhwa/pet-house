<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_persediaan', function (Blueprint $table) {
            $table->id();

            /**
             * KUNCI UTAMA: relasi ke produk
             * - Ini yang bikin mutasi bisa dihitung dan disambungkan dengan POS
             */
            $table->foreignId('produk_id')
                ->constrained('produk')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /**
             * Snapshot data produk (audit trail)
             * - Tetap disimpan agar histori mutasi tetap "as-is" walau produk berubah nama/kode/satuan
             */
            $table->string('kode_produk');
            $table->string('nama_produk');
            $table->string('satuan')->default('pcs');

            // jumlah mutasi (+ masuk / - keluar)
            $table->integer('qty');

            // jenis mutasi
            $table->enum('tipe', ['MASUK', 'KELUAR', 'PENYESUAIAN']);

            // referensi dokumen sumber
            // contoh: PENJUALAN, PEMBELIAN, ADJUSTMENT, dll
            $table->string('ref_tipe')->nullable();

            // id dokumen sumber (penjualans.id / pembelians.id / dll)
            $table->unsignedBigInteger('ref_id')->nullable();

            // harga saat transaksi (opsional tapi berguna)
            $table->decimal('harga', 15, 2)->nullable();

            $table->date('tanggal');
            $table->text('keterangan')->nullable();

            $table->timestamps();

            // index untuk lookup cepat
            $table->index(['produk_id']);
            $table->index(['kode_produk']);
            $table->index(['ref_tipe', 'ref_id']);
            $table->index(['tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_persediaan');
    }
};

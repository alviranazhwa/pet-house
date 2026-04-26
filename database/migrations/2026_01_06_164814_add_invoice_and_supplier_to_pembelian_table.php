<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelian', function (Blueprint $table) {
            // nomor invoice/nota dari supplier (boleh kosong)
            $table->string('no_invoice')->nullable()->after('kode_pembelian');

            // relasi ke supplier (boleh kosong untuk pembelian umum)
            $table->foreignId('supplier_id')
                ->nullable()
                ->after('no_invoice')
                ->constrained('supplier')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // OPTIONAL: lokasi file bukti (foto/pdf)
            $table->string('bukti_url')->nullable()->after('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::table('pembelian', function (Blueprint $table) {
            // drop FK dulu baru kolomnya
            $table->dropForeign(['supplier_id']);

            $table->dropColumn(['no_invoice', 'supplier_id', 'bukti_url']);
        });
    }
};

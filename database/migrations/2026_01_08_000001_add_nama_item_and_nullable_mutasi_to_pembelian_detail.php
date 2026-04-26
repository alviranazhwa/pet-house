<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelian_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('pembelian_detail', 'nama_item')) {
                $table->string('nama_item', 255)->nullable()->after('produk_id');
            }
        });

        /**
         * Ubah mutasi_persediaan_id jadi nullable.
         * NOTE: Kalau kamu pakai MySQL, cara raw SQL ini aman tanpa doctrine/dbal.
         * Pastikan tipe kolom kamu BIGINT UNSIGNED.
         */
        DB::statement("ALTER TABLE pembelian_detail MODIFY mutasi_persediaan_id BIGINT UNSIGNED NULL");
    }

    public function down(): void
    {
        // balikkan jadi NOT NULL (kalau kamu butuh rollback)
        DB::statement("ALTER TABLE pembelian_detail MODIFY mutasi_persediaan_id BIGINT UNSIGNED NOT NULL");

        Schema::table('pembelian_detail', function (Blueprint $table) {
            if (Schema::hasColumn('pembelian_detail', 'nama_item')) {
                $table->dropColumn('nama_item');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelian_detail', function (Blueprint $table) {
            $table->foreignId('produk_id')
                ->nullable()
                ->after('pembelian_id')
                ->constrained('produk')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(['produk_id']);
        });
    }

    public function down(): void
    {
        Schema::table('pembelian_detail', function (Blueprint $table) {
            $table->dropIndex(['produk_id']);
            $table->dropConstrainedForeignId('produk_id');
        });
    }
};

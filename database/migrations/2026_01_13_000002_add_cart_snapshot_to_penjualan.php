<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            if (!Schema::hasColumn('penjualan', 'cart_snapshot')) {
                // simpan snapshot cart (JSON string) agar webhook bisa bikin mutasi+detail pas paid
                $table->longText('cart_snapshot')->nullable()->after('posted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            if (Schema::hasColumn('penjualan', 'cart_snapshot')) {
                $table->dropColumn('cart_snapshot');
            }
        });
    }
};

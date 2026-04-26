<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            if (!Schema::hasColumn('penjualan', 'payment_status')) {
                $table->string('payment_status', 20)->default('PENDING')->after('keterangan');
            }
            if (!Schema::hasColumn('penjualan', 'snap_token')) {
                $table->string('snap_token', 255)->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('penjualan', 'snap_token_created_at')) {
                $table->timestamp('snap_token_created_at')->nullable()->after('snap_token');
            }

            if (!Schema::hasColumn('penjualan', 'transaction_id')) {
                $table->string('transaction_id', 100)->nullable()->after('snap_token_created_at');
            }
            if (!Schema::hasColumn('penjualan', 'payment_type')) {
                $table->string('payment_type', 50)->nullable()->after('transaction_id');
            }
            if (!Schema::hasColumn('penjualan', 'gross_amount')) {
                $table->decimal('gross_amount', 15, 2)->nullable()->after('payment_type');
            }

            if (!Schema::hasColumn('penjualan', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('gross_amount');
            }
            if (!Schema::hasColumn('penjualan', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('paid_at');
            }

            // biar gampang lookup
            $table->index(['payment_status']);
            $table->index(['paid_at']);
            $table->index(['posted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $cols = [
                'payment_status',
                'snap_token',
                'snap_token_created_at',
                'transaction_id',
                'payment_type',
                'gross_amount',
                'paid_at',
                'posted_at',
            ];

            foreach ($cols as $c) {
                if (Schema::hasColumn('penjualan', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};

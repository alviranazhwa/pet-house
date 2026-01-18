<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\KasirCartController;
use App\Http\Controllers\KasirCheckoutController;
use App\Http\Controllers\KasirPaymentController;
use App\Http\Controllers\MidtransWebhookController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/kasir', [KasirController::class, 'index'])->name('kasir.index');

    // Cart actions (session-based)
    Route::post('/kasir/cart/add', [KasirCartController::class, 'add'])->name('kasir.cart.add');
    Route::post('/kasir/cart/update', [KasirCartController::class, 'update'])->name('kasir.cart.update');
    Route::post('/kasir/cart/remove', [KasirCartController::class, 'remove'])->name('kasir.cart.remove');
    Route::post('/kasir/cart/clear', [KasirCartController::class, 'clear'])->name('kasir.cart.clear');

    // checkout -> create PENDING + snap token
    Route::post('/kasir/checkout', [KasirCheckoutController::class, 'store'])->name('kasir.checkout');

    // pay page (snap popup)
    Route::get('/kasir/pay/{penjualan}', [KasirPaymentController::class, 'pay'])->name('kasir.pay');

    // finish page (redirect from snap)
    Route::get('/kasir/finish', [KasirPaymentController::class, 'finish'])->name('kasir.finish');

    // ===== MANUAL SETTLE (temporary workaround for localhost webhook issues) =====
    Route::post('/kasir/manual-settle/{penjualan}', [KasirPaymentController::class, 'manualSettle'])
        ->name('kasir.manualSettle');
});

// webhook midtrans (nggak perlu auth)
Route::post('/midtrans/notification', [MidtransWebhookController::class, 'handle'])->name('midtrans.notification');

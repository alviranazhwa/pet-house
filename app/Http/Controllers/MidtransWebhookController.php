<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Services\MidtransService;
use App\Services\PenjualanFinalizer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request, MidtransService $midtrans, PenjualanFinalizer $finalizer)
    {
        $data = $request->all();

        // 1) Verify signature
        if (!$midtrans->verifySignature($data)) {
            return response()->json(['message' => 'Invalid signature'], Response::HTTP_FORBIDDEN);
        }

        $orderId = (string) ($data['order_id'] ?? '');
        if ($orderId === '') {
            return response()->json(['message' => 'Missing order_id'], Response::HTTP_BAD_REQUEST);
        }

        /** @var Penjualan|null $penjualan */
        $penjualan = Penjualan::query()->where('kode_penjualan', $orderId)->first();
        if (!$penjualan) {
            return response()->json(['message' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $newStatus = $midtrans->mapStatus(
            (string) ($data['transaction_status'] ?? ''),
            (string) ($data['fraud_status'] ?? null)
        );

        // 2) Update payment fields (safe)
        $penjualan->payment_status = $newStatus;
        $penjualan->transaction_id = $data['transaction_id'] ?? $penjualan->transaction_id;
        $penjualan->payment_type   = $data['payment_type'] ?? $penjualan->payment_type;
        $penjualan->gross_amount   = $data['gross_amount'] ?? $penjualan->gross_amount;

        if ($newStatus === 'PAID' && empty($penjualan->paid_at)) {
            $penjualan->paid_at = now();
        }

        $penjualan->save();

        // 3) FINALIZE idempotent (posted_at sebagai guard) + LOCK biar anti dobel webhook/finish
        if ($newStatus === 'PAID') {
            // payment_type bank transfer / VA -> biasanya masuk "bank"
            $finalizer->finalize($penjualan, 'bank');
        }

        return response()->json(['message' => 'OK']);
    }
}

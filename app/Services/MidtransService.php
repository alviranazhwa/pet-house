<?php

namespace App\Services;

use Midtrans\Snap;
use Midtrans\Config as MidtransConfig;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct()
    {
        MidtransConfig::$serverKey    = (string) config('midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('midtrans.is_production');
        MidtransConfig::$isSanitized  = (bool) config('midtrans.is_sanitized', true);
        MidtransConfig::$is3ds        = (bool) config('midtrans.is_3ds', true);
    }

    public function getSnapToken(array $payload): string
    {
        return Snap::getSnapToken($payload);
    }

    /**
     * IMPORTANT:
     * - Midtrans bisa lempar Exception (404, 401, dll)
     * - Kita balikin array yang konsisten: kalau error => ['__error'=>true, 'message'=>...]
     */
    public function getStatus(string $orderId): array
    {
        try {
            $res = Transaction::status($orderId);
            return json_decode(json_encode($res), true);
        } catch (\Throwable $e) {
            return [
                '__error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function verifySignature(array $data): bool
    {
        $serverKey = (string) config('midtrans.server_key');

        $orderId     = (string) ($data['order_id'] ?? '');
        $statusCode  = (string) ($data['status_code'] ?? '');
        $grossAmount = (string) ($data['gross_amount'] ?? '');
        $signature   = (string) ($data['signature_key'] ?? '');

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signature === '') {
            return false;
        }

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        return hash_equals($expected, $signature);
    }

    public function mapStatus(string $transactionStatus, ?string $fraudStatus = null): string
    {
        $ts = strtolower($transactionStatus);

        if ($ts === 'settlement') return 'PAID';

        if ($ts === 'capture') {
            if ($fraudStatus && strtolower($fraudStatus) === 'challenge') return 'REVIEW';
            return 'PAID';
        }

        if ($ts === 'pending') return 'PENDING';
        if ($ts === 'deny')    return 'DENY';
        if ($ts === 'cancel')  return 'CANCEL';
        if ($ts === 'expire')  return 'EXPIRE';
        if ($ts === 'failure') return 'FAILURE';
        if ($ts === 'refund')  return 'REFUND';
        if ($ts === 'chargeback') return 'CHARGEBACK';

        return strtoupper($transactionStatus ?: 'UNKNOWN');
    }
}

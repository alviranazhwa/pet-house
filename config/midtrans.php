<?php

return [
    'server_key'     => env('MIDTRANS_SERVER_KEY', ''),
    'client_key'     => env('MIDTRANS_CLIENT_KEY', ''),
    'is_production'  => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized'   => (bool) env('MIDTRANS_SANITIZED', true),
    'is_3ds'         => (bool) env('MIDTRANS_3DS', true),

    // URL redirect user setelah bayar (finish/unfinish/error)
    'finish_url'     => env('MIDTRANS_FINISH_URL', '/kasir/finish'),
];

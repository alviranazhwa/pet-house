<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hasil Pembayaran</title>

  {{-- kalau masih butuh style kasir lama, boleh tetap dipakai --}}
  <link rel="stylesheet" href="{{ asset('css/kasir.css') }}">
  {{-- style khusus finish --}}
  <link rel="stylesheet" href="{{ asset('css/finish.css') }}">
</head>
<body>

<div class="finish-shell">
  <div class="finish-card">

    {{-- HERO --}}
    <div class="finish-hero">
      <img class="finish-hero__img" src="{{ asset('images/logo_petshop.png') }}" alt="Pet House Logo">

      <div class="finish-hero__title">Terimakasih telah berbelanja</div>
      <div class="finish-hero__subtitle">PET HOUSE GROOMING BANDUNG</div>

      <div class="finish-meta">
        <span>Order: <b>{{ $orderId ?: '-' }}</b></span>
        <span class="dot"></span>
        <span>Snap result: <b>{{ strtoupper($result ?: '-') }}</b></span>
      </div>
    </div>

    <div class="finish-divider"></div>

    @if(!$penjualan)
      <div class="alert alert--err">Order belum ditemukan. Pastikan order_id benar.</div>

      <div class="finish-actions">
        <a class="btnx btnx--success" href="{{ route('kasir.index') }}">Kembali ke Kasir</a>
      </div>

    @else

      @if(!empty($midtransError))
        <div class="alert alert--err">
          ❌ Gagal cek status Midtrans: {{ $midtransError }}<br>
          Coba jalankan <b>php artisan config:clear</b> + <b>php artisan cache:clear</b> lalu refresh halaman ini.
        </div>
      @endif

      <div class="finish-row">
        <div class="finish-label">Status Transaksi</div>
        <div class="finish-badges">
          <span class="badge">Sistem: <b style="margin-left:6px;">{{ $penjualan->payment_status }}</b></span>

          @if(!empty($penjualan->posted_at))
            <span class="badge">POSTED</span>
          @endif
        </div>

        <div class="muted" style="margin-top:8px;">
          Paid at: {{ $penjualan->paid_at ?? '-' }} • Posted at: {{ $penjualan->posted_at ?? '-' }}
        </div>
      </div>

      @if($penjualan->payment_status === 'PAID' && !empty($penjualan->posted_at))
        <div class="alert alert--ok">
          ✅ Pembayaran sudah <b>PAID</b> dan transaksi sudah <b>diposting</b> (stok terpotong + jurnal kebentuk). Keranjang otomatis dibersihkan.
        </div>
      @elseif($penjualan->payment_status === 'PAID' && empty($penjualan->posted_at))
        <div class="alert alert--warn">
          ⚠️ Status <b>PAID</b> tapi belum posted. Refresh sebentar, atau klik “Cek Status”.
        </div>
      @else
        <div class="alert alert--warn">
          ⏳ Status masih <b>{{ $penjualan->payment_status }}</b>. Halaman ini akan auto-refresh beberapa detik.
        </div>
      @endif

      @if(!empty($midtransRaw))
        <div class="finish-row">
          <div class="finish-label">Debug Midtrans status (raw)</div>
          <pre>{{ json_encode($midtransRaw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
      @endif

      <div class="finish-actions">
        <a class="btnx btnx--success" href="{{ route('kasir.index') }}">Kembali ke Kasir</a>

        @if($penjualan->payment_status === 'PENDING')
          <a class="btnx btnx--warning" href="{{ route('kasir.pay', $penjualan->id) }}">Lanjut Bayar</a>
        @endif

        <a class="btnx btnx--info" href="{{ route('kasir.finish', ['order_id'=>$penjualan->kode_penjualan, 'result'=>'manual_refresh']) }}">
          Cek Status
        </a>
      </div>

      @if($penjualan->payment_status === 'PENDING')
        <script>
          setTimeout(() => window.location.reload(), 4000);
        </script>
      @endif

    @endif

  </div>
</div>

</body>
</html>

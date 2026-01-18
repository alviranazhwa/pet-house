<!-- resources/views/kasir/pay.blade.php -->
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Bayar via Midtrans</title>

  {{-- base style kalau masih dipakai --}}
  <link rel="stylesheet" href="{{ asset('css/kasir.css') }}">
  {{-- style khusus halaman pay --}}
  <link rel="stylesheet" href="{{ asset('css/pay.css') }}">

  {{-- Midtrans Snap (SAMA seperti PAY FIX) --}}
  <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ $clientKey }}"></script>
</head>
<body>

<div class="pay-shell">
  <div class="pay-card">

    {{-- HEADER / BRAND --}}
    <div class="pay-hero">
      {{-- ganti path kalau nama file logonya beda --}}
      <img class="pay-hero__img" src="{{ asset('images/logo_petshop.png') }}" alt="Pet House Logo">
      <div class="pay-hero__title">Pembayaran sedang berlangsung</div>
      <div class="pay-hero__subtitle">PET HOUSE GROOMING BANDUNG</div>

      <div class="pay-hero__meta pay-hero__meta--dark">
        <span>Order: <b>{{ $penjualan->kode_penjualan }}</b></span>
        <span class="dot">•</span>
        <span>Total: <b>Rp {{ number_format((float)$penjualan->total, 0, ',', '.') }}</b></span>
      </div>
    </div>

    {{-- OPTIONAL warn dari server (kalau kamu masih mau) --}}
    @if(session('warn'))
      <div class="alert alert--warn" style="margin-top:12px;">⚠️ {{ session('warn') }}</div>
    @endif

    {{-- OPTIONAL error dari server (kalau kamu mau comment, comment blok ini) --}}
    {{--
    @if(!empty($midtransError))
      <div class="alert alert--err" style="margin-top:12px;">
        ❌ Gagal cek status Midtrans: {{ $midtransError }}<br>
        Coba jalankan <b>php artisan optimize:clear</b> lalu refresh.
      </div>
    @endif
    --}}

    {{-- ACTION BUTTONS --}}
    <div class="pay-actions">
      <button class="btnx btnx--warning" id="btnPay" type="button">
        Bayar Sekarang
      </button>

      <a class="btnx btnx--info" href="{{ route('kasir.finish', ['order_id'=>$penjualan->kode_penjualan, 'result'=>'manual_check']) }}">
        Cek Status
      </a>

      <a class="btnx btnx--success" href="{{ route('kasir.index') }}">
        Kembali ke Kasir
      </a>
    </div>

    <div class="pay-note">
      Kalau kamu sudah bayar di Snap dan status di Midtrans sudah <b>settlement</b> tapi sistem masih pending,
      klik <b>Cek Status</b>.
    </div>

    {{-- fallback message kalau popup gak bisa kebuka (hidden by default) --}}
    <div id="snapBlocked" class="pay-hint is-hidden" role="alert">
      Popup Midtrans gagal dibuka. Biasanya karena adblock / firewall / DNS yang nge-block asset Midtrans (gtflabs.io).
      Coba incognito / matiin extension / ganti jaringan.
    </div>

    {{-- MANUAL SETTLE (FULL WIDTH) --}}
    <form class="pay-manual" method="POST" action="{{ route('kasir.manualSettle', $penjualan->id) }}">
      @csrf
      <button type="submit" class="btnx btnx--dark btnx--wide">
        Payment Selesai
      </button>
    </form>

  </div>
</div>

<script>
  // token dari DB (SAMA seperti PAY FIX)
  const snapToken = @json($penjualan->snap_token);

  const btnPay = document.getElementById('btnPay');
  const hint = document.getElementById('snapBlocked');

  let opening = false;

  btnPay.addEventListener('click', function () {
    if (opening) return;

    // basic guard biar ga spam click
    opening = true;
    btnPay.style.opacity = '1';
    btnPay.style.cursor = 'pointer';

    // kalau popup gagal karena asset keblock, biasanya snap.pay throw error.
    // kita kasih timeout agar bisa dicoba lagi.
    setTimeout(() => { opening = false; }, 2000);

    try {
      // kalau snap belum ada, kasih hint (harusnya gak kejadian karena kamu sudah cek typeof)
      if (!window.snap || typeof window.snap.pay !== 'function') {
        if (hint) hint.classList.remove('is-hidden');
        opening = false;
        return;
      }

      // ====== CALL SNAP PAY (SAMA seperti PAY FIX) ======
      window.snap.pay(snapToken, {
        onSuccess: function(result){
          window.location.href = "{{ route('kasir.finish') }}" + "?order_id={{ $penjualan->kode_penjualan }}&result=success";
        },
        onPending: function(result){
          window.location.href = "{{ route('kasir.finish') }}" + "?order_id={{ $penjualan->kode_penjualan }}&result=pending";
        },
        onError: function(result){
          // kalau error, biar bisa klik lagi
          opening = false;
          window.location.href = "{{ route('kasir.finish') }}" + "?order_id={{ $penjualan->kode_penjualan }}&result=error";
        },
        onClose: function(){
          // user nutup popup
          opening = false;
        }
      });
    } catch (e) {
      // kalau popup asset keblock, biasanya error muncul di sini
      opening = false;
      if (hint) hint.classList.remove('is-hidden');
      console.error(e);
    }
  });
</script>

</body>
</html>

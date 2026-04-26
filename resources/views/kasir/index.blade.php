<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PENJUALAN PET HOUSE</title>

  <link rel="stylesheet" href="{{ asset('css/kasir.css') }}">
</head>
<body>
<div class="pos-shell">

  {{-- TOP BAR --}}
  <div class="pos-topbar">
    <div>
      <div class="pos-title">KASIR PET HOUSE GROOMING BANDUNG</div>
    </div>

    <div class="pos-actions">
      <a class="btn" href="{{ url('/admin') }}">← Back to Admin</a>
    </div>
  </div>

  {{-- ALERTS (kalau mau dimatiin total, nanti matiin session ok di controller add) --}}
  @if(session('ok'))
    <div class="alert alert--ok">✅ {{ session('ok') }}</div>
  @endif
  @if(session('err'))
    <div class="alert alert--err">❌ {{ session('err') }}</div>
  @endif
  @if(session('warn'))
    <div class="alert alert--warn">⚠️ {{ session('warn') }}</div>
  @endif

  {{-- CONTENT --}}
  <div class="pos-grid">

    {{-- LEFT: PRODUK --}}
    <div class="panel">
      <div class="panel-head">
        <h2 class="panel-title">Produk</h2>

        {{-- SEARCH BAR (LIVE) --}}
        <div class="search-wrap">
          <input
            class="search"
            id="searchInput"
            type="text"
            placeholder="Cari produk (nama / kode / kategori)..."
            autocomplete="off"
          />

          {{-- icon-only (ga perlu klik, buat estetika + fokus input) --}}
          <button class="icon-btn icon-btn--yellow" type="button" id="btnSearch" title="Cari">
            <x-heroicon-o-magnifying-glass class="icon" />
          </button>
        </div>
      </div>

      <div class="panel-body">
        <div id="productGrid" class="product-grid">
          @php $fallback = asset('images/makanan_kucing.png'); @endphp

          @forelse ($produks as $p)
            @php
              $nama = $p->nama_produk ?? '';
              $kode = $p->kode_produk ?? '';
              $kategori = $p->kategori->nama_kategori ?? 'Tanpa Kategori';

              $stok = \App\Models\MutasiPersediaan::stokSaatIni($p->id);
              $menipis = $stok > 0 && $stok < 5;
              $habis = $stok <= 0;

              $img = !empty($p->image_url) ? asset($p->image_url) : $fallback;

              $searchKey = strtolower(trim($nama . ' ' . $kode . ' ' . $kategori));
            @endphp

            <div class="product-card {{ $habis ? 'is-out' : '' }}" data-search="{{ e($searchKey) }}">
              <div class="product-thumb">
                <img src="{{ $img }}" alt="{{ $nama }}" loading="lazy">
              </div>

              <div class="product-content">
                <p class="product-name">{{ $nama }}</p>
                <p class="product-meta">{{ $kategori }} • {{ $p->satuan }}</p>

                <div class="stockline">
                  <span class="stockline__count">
                    Stok: <b>{{ $stok }}</b>
                    <span class="product-code">• {{ $kode }}</span>
                  </span>

                  @if($habis)
                    <span class="badge badge--out">Habis</span>
                  @elseif($menipis)
                    <span class="badge badge--warn">Menipis</span>
                  @endif
                </div>

                <div class="product-row">
                  <div class="product-price">
                    Rp {{ number_format((float) $p->harga_jual, 0, ',', '.') }}
                  </div>

                  <form method="POST" action="{{ route('kasir.cart.add') }}">
                    @csrf
                    <input type="hidden" name="produk_id" value="{{ $p->id }}">

                    <button
                      type="submit"
                      class="product-add"
                      {{ $habis ? 'disabled' : '' }}
                      title="{{ $habis ? 'Stok habis' : 'Tambah ke keranjang' }}"
                    >
                      +
                    </button>
                  </form>
                </div>
              </div>
            </div>

          @empty
            <div class="cart-empty" style="grid-column:1/-1;">
              Produk masih kosong. Isi dari <b>Filament → Produk</b>.
            </div>
          @endforelse
        </div>

        <div id="searchEmpty" class="cart-empty is-hidden" style="margin-top:12px;">
          Tidak ada produk yang cocok 😿
        </div>
      </div>
    </div>

    {{-- RIGHT: KERANJANG --}}
    <div class="panel">
      <div class="panel-head">
        <h2 class="panel-title">Keranjang</h2>
        <span class="badge">Items: {{ $summary['items'] ?? 0 }}</span>
      </div>

      <div class="panel-body">
        @if (empty($cart))
          <div class="cart-empty">
            Keranjang masih kosong.<br>
            Klik tombol <b>+</b> pada produk.
          </div>
        @else
          <div class="cart-stack">

            <div class="cart-list">
              @foreach ($cart as $row)
                <div class="cart-item">
                  <div class="cart-item__top">
                    <div>
                      <div class="cart-item__name">{{ $row['nama_produk'] }}</div>
                      <div class="cart-item__meta">
                        Rp {{ number_format((float) $row['harga_jual'], 0, ',', '.') }} / {{ $row['satuan'] }}
                      </div>
                    </div>

                    <div class="cart-item__remove">
                      <form method="POST" action="{{ route('kasir.cart.remove') }}">
                        @csrf
                        <input type="hidden" name="produk_id" value="{{ $row['produk_id'] }}">
                        <button class="btn btn-sm" type="submit" title="Hapus item">✕</button>
                      </form>
                    </div>
                  </div>

                  <div class="cart-item__bottom">
                    <form method="POST" action="{{ route('kasir.cart.update') }}" class="cart-qty-form">
                      @csrf
                      <input type="hidden" name="produk_id" value="{{ $row['produk_id'] }}">

                      <input
                        type="number"
                        name="qty"
                        min="1"
                        value="{{ $row['qty'] }}"
                        class="cart-qty-input"
                      >

                      <button class="icon-btn icon-btn--yellow" type="submit" title="Update qty">
                        <x-heroicon-o-arrow-path class="icon" />
                      </button>
                    </form>

                    <div class="cart-item__subtotal">
                      Rp {{ number_format(((float) $row['harga_jual']) * (int) $row['qty'], 0, ',', '.') }}
                    </div>
                  </div>
                </div>
              @endforeach
            </div>

            <div class="cart-summary">
              <div class="cart-summary__label">Total</div>
              <div class="cart-summary__total">
                Rp {{ number_format((float) ($summary['total'] ?? 0), 0, ',', '.') }}
              </div>
            </div>

            {{-- CLEAR KERANJANG = panjang (wide) + icon --}}
            <form method="POST" action="{{ route('kasir.cart.clear') }}">
              @csrf
              <button class="icon-btn icon-btn--danger icon-btn--wide" type="submit" title="Clear Keranjang">
                <x-heroicon-o-trash class="icon" />
              </button>
            </form>

            <form method="POST" action="{{ route('kasir.checkout') }}">
              @csrf

              {{-- METODE BAYAR = 2 tombol kiri-kanan (segmented) --}}
              <div class="pay-segment" role="radiogroup" aria-label="Metode bayar">
                <label class="pay-pill">
                  <input type="radio" name="mode" value="kas" checked>
                  <span class="pay-pill__inner">
                    <span class="pay-pill__icon">
                      <x-heroicon-o-banknotes class="icon" />
                    </span>
                    <span class="pay-pill__text">Cash</span>
                  </span>
                </label>

                <label class="pay-pill">
                  <input type="radio" name="mode" value="bank">
                  <span class="pay-pill__inner">
                    <span class="pay-pill__icon">
                      <x-heroicon-o-credit-card class="icon" />
                    </span>
                    <span class="pay-pill__text">Bank</span>
                  </span>
                </label>
              </div>

              {{-- BAYAR = line dulu, hover baru fill hijau --}}
              <button class="btn-pay btn-wide" type="submit">
                <x-heroicon-o-banknotes class="icon" />
                <span>Bayar</span>
              </button>
            </form>

          </div>
        @endif
      </div>
    </div>

  </div>
</div>

<script>
  // ===== LIVE SEARCH (client-side) =====
  (function () {
    const input = document.getElementById('searchInput');
    const grid  = document.getElementById('productGrid');
    const empty = document.getElementById('searchEmpty');
    if (!input || !grid) return;

    const cards = Array.from(grid.querySelectorAll('.product-card'));

    const normalize = (s) => (s || '')
      .toString()
      .toLowerCase()
      .trim()
      .replace(/\s+/g, ' ');

    let t = null;

    function applyFilter() {
      const q = normalize(input.value);
      let shown = 0;

      cards.forEach(card => {
        const key = normalize(card.getAttribute('data-search') || '');
        const hit = q === '' || key.includes(q);

        card.classList.toggle('is-hidden', !hit);
        if (hit) shown++;
      });

      if (empty) empty.classList.toggle('is-hidden', shown !== 0);
    }

    input.addEventListener('input', function () {
      clearTimeout(t);
      t = setTimeout(applyFilter, 60);
    });

    const btn = document.getElementById('btnSearch');
    if (btn) btn.addEventListener('click', () => input.focus());

    applyFilter();
  })();
</script>
</body>
</html>

<x-filament-panels::page>
    {{-- FILTER (tanpa tombol di dalam card, karena tombol sudah ada di kanan atas header) --}}
    <x-filament::section>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <div style="font-size:24px; font-weight:700;">Laba Rugi</div>

            <div style="font-size:14px; color:#6b7280;">
                Ringkasan laba rugi berdasarkan jurnal detail.
                <span style="margin-left:8px; font-weight:600; color:#111827;">
                    Periode: {{ $this->periodeLabel ?? '-' }}
                </span>
            </div>
        </div>

        {{-- FILTER UI --}}
        <div style="margin-top:16px;">
            <div style="
                display:grid;
                grid-template-columns: repeat(12, 1fr);
                gap:12px;
                align-items:end;
            ">

                {{-- Dari --}}
                <div style="grid-column: span 3;">
                    <label style="display:block; font-size:12px; color:#6b7280; margin-bottom:6px;">
                        Dari <span style="color:#ef4444;">*</span>
                    </label>
                    <input
                        type="date"
                        wire:model.defer="from"
                        style="
                            width:100%;
                            border:1px solid #d1d5db;
                            border-radius:10px;
                            padding:10px 12px;
                            background:#fff;
                        "
                    />
                </div>

                {{-- Sampai --}}
                <div style="grid-column: span 3;">
                    <label style="display:block; font-size:12px; color:#6b7280; margin-bottom:6px;">
                        Sampai <span style="color:#ef4444;">*</span>
                    </label>
                    <input
                        type="date"
                        wire:model.defer="until"
                        style="
                            width:100%;
                            border:1px solid #d1d5db;
                            border-radius:10px;
                            padding:10px 12px;
                            background:#fff;
                        "
                    />
                </div>

                {{-- Quick Bulan --}}
                {{-- <div style="grid-column: span 4;">
                    <label style="display:block; font-size:12px; color:#6b7280; margin-bottom:6px;">
                        Bulan (opsional)
                    </label>
                    <input
                        type="month"
                        wire:model.defer="month"
                        style="
                            width:100%;
                            border:1px solid #d1d5db;
                            border-radius:10px;
                            padding:10px 12px;
                            background:#fff;
                        "
                    />
                    <div style="margin-top:6px; font-size:12px; color:#6b7280;">
                        Isi bulan biar auto set periode 1 bulan penuh (lebih sat-set).
                    </div>
                </div> --}}

                {{-- Info kecil (opsional) --}}
                {{-- <div style="grid-column: span 2; text-align:right;">
                    <div style="font-size:12px; color:#6b7280;">
                        Tombol aksi ada di kanan atas 👆
                    </div>
                </div> --}}
            </div>
        </div>
    </x-filament::section>

    {{-- TABLE RINGKASAN --}}
    @php
        $s = $this->summary ?? [];

        $pendapatan = (float) ($s['pendapatan'] ?? 0);
        $hpp        = (float) ($s['hpp'] ?? 0);
        $labaKotor  = (float) ($s['laba_kotor'] ?? ($pendapatan - $hpp));
        $beban      = (float) ($s['beban_operasional'] ?? 0);
        $labaBersih = (float) ($s['laba_bersih'] ?? ($labaKotor - $beban));
    @endphp

    <x-filament::section style="margin-top:24px;">
        <div style="display:flex; flex-direction:column; gap:10px;">
            <div style="font-size:16px; font-weight:600; color:#111827;">
                Ringkasan Laba Rugi
            </div>

            <div style="overflow-x:auto;">
                <table style="
                    width:100%;
                    border-collapse:collapse;
                    font-size:14px;
                    background:#ffffff;
                    border:1px solid #d1d5db;
                    border-radius:12px;
                    overflow:hidden;
                ">
                    <thead>
                        <tr style="background:#f3f4f6;">
                            <th style="border:1px solid #d1d5db; padding:12px 14px; text-align:left; font-weight:600;">
                                Keterangan
                            </th>
                            <th style="border:1px solid #d1d5db; padding:12px 14px; text-align:right; width:240px; font-weight:600;">
                                Jumlah
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr style="background:#ffffff;">
                            <td style="border:1px solid #e5e7eb; padding:12px 14px;">Pendapatan</td>
                            <td style="border:1px solid #e5e7eb; padding:12px 14px; text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums;">
                                Rp {{ number_format($pendapatan, 0, ',', '.') }}
                            </td>
                        </tr>

                        <tr style="background:#f9fafb;">
                            <td style="border:1px solid #e5e7eb; padding:12px 14px;">HPP</td>
                            <td style="border:1px solid #e5e7eb; padding:12px 14px; text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums;">
                                Rp {{ number_format($hpp, 0, ',', '.') }}
                            </td>
                        </tr>

                        <tr style="background:#ffffff;">
                            <td style="border:1px solid #e5e7eb; padding:12px 14px;">Laba Kotor</td>
                            <td style="border:1px solid #e5e7eb; padding:12px 14px; text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums;">
                                Rp {{ number_format($labaKotor, 0, ',', '.') }}
                            </td>
                        </tr>

                        <tr style="background:#f9fafb;">
                            <td style="border:1px solid #e5e7eb; padding:12px 14px;">Beban Operasional</td>
                            <td style="border:1px solid #e5e7eb; padding:12px 14px; text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums;">
                                Rp {{ number_format($beban, 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Laba Bersih (kalau kamu mau gak bold: ganti font-weight jadi 400) --}}
                        <tr style="background:#eef2ff;">
                            <td style="border:1px solid #d1d5db; padding:12px 14px; font-weight:600;">
                                Laba Bersih
                            </td>
                            <td style="border:1px solid #d1d5db; padding:12px 14px; text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; font-weight:600;">
                                Rp {{ number_format($labaBersih, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>

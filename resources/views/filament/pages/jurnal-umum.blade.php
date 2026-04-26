<x-filament-panels::page>
    {{-- HEADER + FILTER --}}
    <x-filament::section>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <div style="font-size:24px; font-weight:700;">Jurnal Umum</div>

            <div style="font-size:14px; color:#6b7280;">
                Tampilan jurnal umum dalam satu tabel (standar akuntansi).
                <span style="margin-left:8px; font-weight:600; color:#111827;">
                    Periode: {{ $this->periodeLabel }}
                </span>
            </div>
        </div>

        <div style="margin-top:16px; display:grid; grid-template-columns: repeat(12, 1fr); gap:12px;">
            {{-- Dari --}}
            <div style="grid-column: span 3;">
                <label style="display:block; font-size:12px; color:#6b7280; margin-bottom:6px;">Dari</label>
                <input
                    type="date"
                    wire:model.defer="from"
                    style="width:100%; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px;"
                >
            </div>

            {{-- Sampai --}}
            <div style="grid-column: span 3;">
                <label style="display:block; font-size:12px; color:#6b7280; margin-bottom:6px;">Sampai</label>
                <input
                    type="date"
                    wire:model.defer="until"
                    style="width:100%; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px;"
                >
            </div>

            {{-- Akun --}}
            <div style="grid-column: span 3;">
                <label style="display:block; font-size:12px; color:#6b7280; margin-bottom:6px;">Akun (Opsional)</label>
                <select
                    wire:model.defer="akun_id"
                    style="width:100%; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px; background:#fff;"
                >
                    <option value="">Semua akun</option>
                    @foreach ($this->akunOptions as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Search --}}
            <div style="grid-column: span 3;">
                <label style="display:block; font-size:12px; color:#6b7280; margin-bottom:6px;">Search</label>
                <div style="position:relative;">
                    <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#9ca3af;">
                        🔎
                    </span>
                    <input
                        type="text"
                        wire:model.defer="q"
                        placeholder="Cari nomor jurnal / ref / akun…"
                        style="width:100%; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px 10px 34px;"
                    >
                </div>
            </div>
        </div>

        <div style="margin-top:14px; display:flex; flex-wrap:wrap; gap:8px;">
            <x-filament::button wire:click="apply" color="success" icon="heroicon-o-eye">
                Tampilkan
            </x-filament::button>

            <x-filament::button wire:click="resetFilter" color="gray" icon="heroicon-o-x-mark">
                Reset
            </x-filament::button>
        </div>
    </x-filament::section>

    {{-- TABLE (ONE TABLE ONLY) --}}
    <x-filament::section style="margin-top:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div style="font-size:16px; font-weight:700;">Data Jurnal Umum</div>

            <div style="font-size:14px; color:#111827;">
                {{-- <span style="color:#6b7280;">Total Debit:</span>
                <span style="font-weight:700; margin-right:12px;">
                    Rp {{ number_format($this->totalDebit, 0, ',', '.') }}
                </span>

                <span style="color:#6b7280;">Total Kredit:</span>
                <span style="font-weight:700;">
                    Rp {{ number_format($this->totalKredit, 0, ',', '.') }}
                </span> --}}
            </div>
        </div>

        <div style="margin-top:14px; overflow-x:auto;">
            <table style="
                width:100%;
                min-width:980px;
                border-collapse:collapse;
                font-size:14px;
                background:#ffffff;
                border:1px solid #d1d5db;
                border-radius:12px;
                overflow:hidden;
            ">
                <thead>
                    <tr style="background:#f3f4f6;">
                        <th style="border:1px solid #d1d5db; padding:10px 12px; text-align:left; width:130px; font-weight:600;">
                            Tanggal
                        </th>
                        <th style="border:1px solid #d1d5db; padding:10px 12px; text-align:left; width:200px; font-weight:600;">
                            No. Jurnal
                        </th>
                        <th style="border:1px solid #d1d5db; padding:10px 12px; text-align:left; width:180px; font-weight:600;">
                            Ref
                        </th>
                        <th style="border:1px solid #d1d5db; padding:10px 12px; text-align:left; font-weight:600;">
                            Akun
                        </th>
                        <th style="border:1px solid #d1d5db; padding:10px 12px; text-align:right; width:160px; font-weight:600;">
                            Debit
                        </th>
                        <th style="border:1px solid #d1d5db; padding:10px 12px; text-align:right; width:160px; font-weight:600;">
                            Kredit
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @if (empty($this->rows))
                        <tr>
                            <td colspan="6" style="padding:16px; text-align:center; color:#6b7280;">
                                Tidak ada data untuk filter/periode yang dipilih.
                            </td>
                        </tr>
                    @else
                        @foreach ($this->rows as $i => $r)
                            <tr style="background: {{ $i % 2 === 0 ? '#ffffff' : '#f9fafb' }};">
                                <td style="border:1px solid #e5e7eb; padding:10px 12px; white-space:nowrap;">
                                    {{ $r['tanggal'] }}
                                </td>

                                <td style="border:1px solid #e5e7eb; padding:10px 12px; white-space:nowrap;">
                                    {{ $r['nomor'] }}
                                </td>

                                <td style="border:1px solid #e5e7eb; padding:10px 12px; white-space:nowrap;">
                                    {{ $r['ref'] }}
                                </td>

                                <td style="border:1px solid #e5e7eb; padding:10px 12px;">
                                    {{ $r['akun'] }}
                                </td>

                                <td style="border:1px solid #e5e7eb; padding:10px 12px; text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums;">
                                    @if (($r['debit'] ?? 0) > 0)
                                        Rp {{ number_format($r['debit'], 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td style="border:1px solid #e5e7eb; padding:10px 12px; text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums;">
                                    @if (($r['kredit'] ?? 0) > 0)
                                        Rp {{ number_format($r['kredit'], 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        {{-- TOTAL ROW (paling bawah) --}}
                        <tr style="background:#eef2ff;">
                            <td colspan="4" style="border:1px solid #d1d5db; padding:10px 12px; font-weight:700;">
                                TOTAL
                            </td>

                            <td style="border:1px solid #d1d5db; padding:10px 12px; text-align:right; font-weight:700; white-space:nowrap; font-variant-numeric:tabular-nums;">
                                Rp {{ number_format($this->totalDebit, 0, ',', '.') }}
                            </td>

                            <td style="border:1px solid #d1d5db; padding:10px 12px; text-align:right; font-weight:700; white-space:nowrap; font-variant-numeric:tabular-nums;">
                                Rp {{ number_format($this->totalKredit, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>

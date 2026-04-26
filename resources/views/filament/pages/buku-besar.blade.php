<x-filament-panels::page>
    {{-- HEADER + FILTER --}}
    <x-filament::section>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <div style="font-size:24px; font-weight:700;">Buku Besar</div>

            <div style="font-size:14px; color:#6b7280;">
                Tampilkan buku besar per akun berdasarkan jurnal detail.
                <span style="margin-left:8px; font-weight:600; color:#111827;">
                    Periode: {{ $this->periodeLabel }}
                </span>
            </div>
        </div>

        <div style="margin-top:16px; display:flex; flex-direction:column; gap:12px;">
            {{ $this->form }}

            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                <x-filament::button wire:click="tampilkan" color="success">
                    Tampilkan
                </x-filament::button>

                {{-- <x-filament::button wire:click="downloadPdf" color="gray">
                    PDF (sesuai pilihan)
                </x-filament::button> --}}
            </div>
        </div>
    </x-filament::section>

    {{-- EMPTY STATE --}}
    @if (empty($this->ledgers))
        <x-filament::section style="margin-top:24px;">
            <div style="font-size:14px; color:#6b7280;">
                Tidak ada data untuk periode / filter yang dipilih.
            </div>
        </x-filament::section>
    @else
        <div style="margin-top:24px; display:flex; flex-direction:column; gap:24px;">
            @foreach ($this->ledgers as $ledger)
                <x-filament::section>
                    {{-- HEADER AKUN --}}
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <div style="font-size:18px; font-weight:600; color:#111827;">
                            {{ $ledger['akun']['kode_akun'] }} - {{ $ledger['akun']['nama_akun'] }}
                        </div>

                        <div style="font-size:14px; color:#374151;">
                            <span style="color:#6b7280; font-weight:600;">Saldo Awal:</span>
                            <span style="font-weight:600;">
                                Rp {{ number_format($ledger['opening']['amount'], 0, ',', '.') }} ({{ $ledger['opening']['side'] }})
                            </span>

                            <span style="margin:0 10px; color:#9ca3af;">•</span>

                            <span style="color:#6b7280; font-weight:600;">Total Debit:</span>
                            <span style="font-weight:600;">
                                Rp {{ number_format($ledger['total_debit'], 0, ',', '.') }}
                            </span>

                            <span style="margin:0 10px; color:#9ca3af;">•</span>

                            <span style="color:#6b7280; font-weight:600;">Total Credit:</span>
                            <span style="font-weight:600;">
                                Rp {{ number_format($ledger['total_kredit'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    {{-- TABLE WRAPPER --}}
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
                                    <th style="border:1px solid #d1d5db; padding:10px 12px; text-align:left; width:120px; font-weight:400;">
                                        Tanggal
                                    </th>
                                    <th style="border:1px solid #d1d5db; padding:10px 12px; text-align:left; width:200px; font-weight:400;">
                                        No.
                                    </th>
                                    <th style="border:1px solid #d1d5db; padding:10px 12px; text-align:left; font-weight:400;">
                                        Keterangan
                                    </th>
                                    <th style="border:1px solid #d1d5db; padding:10px 12px; text-align:right; width:150px; font-weight:400;">
                                        Debit
                                    </th>
                                    <th style="border:1px solid #d1d5db; padding:10px 12px; text-align:right; width:150px; font-weight:400;">
                                        Credit
                                    </th>
                                    <th style="border:1px solid #d1d5db; padding:10px 12px; text-align:right; width:180px; font-weight:400;">
                                        Saldo (D/C)
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($ledger['rows'] as $i => $row)
                                    <tr style="background: {{ $i % 2 === 0 ? '#ffffff' : '#f9fafb' }};">
                                        <td style="border:1px solid #e5e7eb; padding:10px 12px; white-space:nowrap;">
                                            {{ $row['tanggal'] }}
                                        </td>

                                        <td style="border:1px solid #e5e7eb; padding:10px 12px; white-space:nowrap;">
                                            {{ $row['nomor'] }}
                                        </td>

                                        <td style="border:1px solid #e5e7eb; padding:10px 12px;">
                                            {{ $row['keterangan'] ?: '-' }}
                                        </td>

                                        <td style="border:1px solid #e5e7eb; padding:10px 12px; text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums;">
                                            @if (($row['debit'] ?? 0) > 0)
                                                Rp {{ number_format($row['debit'], 0, ',', '.') }}
                                            @else
                                                —
                                            @endif
                                        </td>

                                        <td style="border:1px solid #e5e7eb; padding:10px 12px; text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums;">
                                            @if (($row['kredit'] ?? 0) > 0)
                                                Rp {{ number_format($row['kredit'], 0, ',', '.') }}
                                            @else
                                                —
                                            @endif
                                        </td>

                                        <td style="border:1px solid #e5e7eb; padding:10px 12px; text-align:right; white-space:nowrap; font-weight:400; font-variant-numeric:tabular-nums;">
                                            Rp {{ number_format($row['saldo_amount'], 0, ',', '.') }} ({{ $row['saldo_side'] }})
                                        </td>
                                    </tr>
                                @endforeach

                                {{-- TOTAL ROW --}}
                                <tr style="background:#eef2ff;">
                                    <td colspan="3" style="border:1px solid #d1d5db; padding:10px 12px; font-weight:400;">
                                        Total
                                    </td>

                                    <td style="border:1px solid #d1d5db; padding:10px 12px; text-align:right; font-weight:400; white-space:nowrap; font-variant-numeric:tabular-nums;">
                                        Rp {{ number_format($ledger['total_debit'], 0, ',', '.') }}
                                    </td>

                                    <td style="border:1px solid #d1d5db; padding:10px 12px; text-align:right; font-weight:400; white-space:nowrap; font-variant-numeric:tabular-nums;">
                                        Rp {{ number_format($ledger['total_kredit'], 0, ',', '.') }}
                                    </td>

                                    <td style="border:1px solid #d1d5db; padding:10px 12px; text-align:right; font-weight:400; white-space:nowrap; font-variant-numeric:tabular-nums;">
                                        Rp {{ number_format($ledger['ending']['amount'], 0, ',', '.') }} ({{ $ledger['ending']['side'] }})
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>

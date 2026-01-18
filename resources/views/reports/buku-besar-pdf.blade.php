<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Buku Besar</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .subtitle { font-size: 12px; color: #555; margin-bottom: 16px; }
        .card { border: 1px solid #ddd; border-radius: 10px; padding: 12px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e5e5; padding: 8px; }
        th { background: #f5f5f5; text-align: left; }
        .right { text-align: right; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <div class="title">Buku Besar</div>
    <div class="subtitle">Periode: <b>{{ $periodeLabel }}</b></div>

    @foreach ($ledgers as $ledger)
        <div class="card">
            <div style="font-size: 14px; font-weight: bold; margin-bottom: 6px;">
                {{ $ledger['akun']['kode_akun'] }} - {{ $ledger['akun']['nama_akun'] }}
            </div>

            <div class="muted" style="margin-bottom: 10px;">
                Saldo Awal: <b>Rp {{ number_format($ledger['opening']['amount'], 0, ',', '.') }} ({{ $ledger['opening']['side'] }})</b>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No.</th>
                        <th>Keterangan</th>
                        <th class="right">Debit</th>
                        <th class="right">Credit</th>
                        <th class="right">Saldo (D/C)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ledger['rows'] as $row)
                        <tr>
                            <td>{{ $row['tanggal'] }}</td>
                            <td>{{ $row['nomor'] }}</td>
                            <td>{{ $row['keterangan'] ?: '-' }}</td>
                            <td class="right">Rp {{ number_format($row['debit'], 0, ',', '.') }}</td>
                            <td class="right">Rp {{ number_format($row['kredit'], 0, ',', '.') }}</td>
                            <td class="right">Rp {{ number_format($row['saldo_amount'], 0, ',', '.') }} ({{ $row['saldo_side'] }})</td>
                        </tr>
                    @endforeach

                    <tr>
                        <td colspan="3"><b>Total</b></td>
                        <td class="right"><b>Rp {{ number_format($ledger['total_debit'], 0, ',', '.') }}</b></td>
                        <td class="right"><b>Rp {{ number_format($ledger['total_kredit'], 0, ',', '.') }}</b></td>
                        <td class="right"><b>Rp {{ number_format($ledger['ending']['amount'], 0, ',', '.') }} ({{ $ledger['ending']['side'] }})</b></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>

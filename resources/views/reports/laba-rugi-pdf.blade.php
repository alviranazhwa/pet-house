<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laba Rugi</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 6px; }
        .muted { color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        td { border: 1px solid #ddd; padding: 10px; }
        td.right { text-align: right; }
        tr.heading td { background: #f3f3f3; font-weight: bold; }
    </style>
</head>
<body>
    <div class="title">Laba Rugi</div>
    <div class="muted">Periode: {{ $periode }}</div>

    <table>
        <tr>
            <td>Pendapatan</td>
            <td class="right">Rp {{ number_format($summary['pendapatan'] ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>HPP</td>
            <td class="right">Rp {{ number_format($summary['hpp'] ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr class="heading">
            <td>Laba Kotor</td>
            <td class="right">Rp {{ number_format($summary['laba_kotor'] ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Beban Operasional</td>
            <td class="right">Rp {{ number_format($summary['beban_operasional'] ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr class="heading">
            <td>Laba Bersih</td>
            <td class="right">Rp {{ number_format($summary['laba_bersih'] ?? 0, 0, ',', '.') }}</td>
        </tr>
    </table>
</body>
</html>

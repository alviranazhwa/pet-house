<x-filament-panels::page>

{{-- HEADER + FILTER --}}
<x-filament::section>

    <div style="display:flex; flex-direction:column; gap:6px;">
        <div style="font-size:24px; font-weight:700;">Kartu Stok</div>

        <div style="font-size:14px; color:#6b7280;">
            Kartu persediaan metode Average (Perpetual)
        </div>
    </div>

    <div style="margin-top:16px; display:grid; grid-template-columns: repeat(12, 1fr); gap:12px;">

        {{-- Produk --}}
        <div style="grid-column: span 4;">
            <label style="font-size:12px;color:#6b7280;margin-bottom:6px;display:block">Produk</label>
            <select wire:model.defer="produk_id"
                style="width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;">
                @foreach($produkOptions as $id=>$label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Dari --}}
        <div style="grid-column: span 3;">
            <label style="font-size:12px;color:#6b7280;margin-bottom:6px;display:block">Dari</label>
            <input type="date" wire:model.defer="from"
                style="width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;">
        </div>

        {{-- Sampai --}}
        <div style="grid-column: span 3;">
            <label style="font-size:12px;color:#6b7280;margin-bottom:6px;display:block">Sampai</label>
            <input type="date" wire:model.defer="until"
                style="width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;">
        </div>

        {{-- Button --}}
        <div style="grid-column: span 2;align-self:end">
            <x-filament::button wire:click="apply" color="success">
                Terapkan
            </x-filament::button>
        </div>

    </div>

</x-filament::section>

{{-- SUMMARY --}}
<x-filament::section style="margin-top:20px">

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">

    <div style="border:1px solid #e5e7eb;border-radius:10px;padding:12px">
        <div style="font-size:12px;color:#6b7280">Saldo Qty</div>
        <div style="font-size:20px;font-weight:700">{{ $summary['qty'] }}</div>
    </div>

    <div style="border:1px solid #e5e7eb;border-radius:10px;padding:12px">
        <div style="font-size:12px;color:#6b7280">Avg Harga</div>
        <div style="font-size:20px;font-weight:700">
            Rp {{ number_format($summary['avg'],0,',','.') }}
        </div>
    </div>

    <div style="border:1px solid #e5e7eb;border-radius:10px;padding:12px">
        <div style="font-size:12px;color:#6b7280">Nilai Persediaan</div>
        <div style="font-size:20px;font-weight:700">
            Rp {{ number_format($summary['nilai'],0,',','.') }}
        </div>
    </div>

</div>

</x-filament::section>

{{-- TABLE --}}
<x-filament::section style="margin-top:24px">

<div style="overflow-x:auto">

<table style="
width:100%;
min-width:900px;
border-collapse:collapse;
font-size:14px;
border:1px solid #d1d5db;
border-radius:12px;
overflow:hidden;
">

<thead>
<tr style="background:#f3f4f6">
<th style="padding:10px;border:1px solid #d1d5db;width:120px">Tanggal</th>
<th style="padding:10px;border:1px solid #d1d5db">Ref</th>
<th style="padding:10px;border:1px solid #d1d5db;width:90px;text-align:center">Masuk</th>
<th style="padding:10px;border:1px solid #d1d5db;width:90px;text-align:center">Keluar</th>
<th style="padding:10px;border:1px solid #d1d5db;width:90px;text-align:center">Saldo</th>
<th style="padding:10px;border:1px solid #d1d5db;width:130px;text-align:right">Avg</th>
<th style="padding:10px;border:1px solid #d1d5db;width:150px;text-align:right">Nilai</th>
</tr>
</thead>

<tbody>

@php $last=null; @endphp

@foreach($rows as $i=>$r)

@if($last!==$r['group'])
<tr style="background:#e5e7eb;font-weight:700">
<td colspan="7" style="padding:8px 12px">
{{ strtoupper($r['group']) }}
</td>
</tr>
@php $last=$r['group']; @endphp
@endif

<tr style="background:{{ $i%2?'#f9fafb':'#fff' }}">

<td style="border:1px solid #e5e7eb;padding:8px">{{ $r['tanggal'] }}</td>

<td style="border:1px solid #e5e7eb;padding:8px">{{ $r['ref'] }}</td>

<td style="border:1px solid #e5e7eb;padding:8px;text-align:center;color:#16a34a">
{{ $r['masuk'] ?: '-' }}
</td>

<td style="border:1px solid #e5e7eb;padding:8px;text-align:center;color:#dc2626">
{{ $r['keluar'] ?: '-' }}
</td>

<td style="border:1px solid #e5e7eb;padding:8px;text-align:center">
{{ $r['saldo'] }}
</td>

<td style="border:1px solid #e5e7eb;padding:8px;text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums">
Rp {{ number_format($r['avg'],0,',','.') }}
</td>

<td style="border:1px solid #e5e7eb;padding:8px;text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums">
Rp {{ number_format($r['nilai'],0,',','.') }}
</td>

</tr>

@endforeach

</tbody>
</table>

</div>

</x-filament::section>

</x-filament-panels::page>

<?php

namespace App\Filament\Resources\JurnalDetails\Tables;

use App\Models\Akun;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JurnalDetailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Tanggal (dari header jurnal)
                TextColumn::make('jurnal.tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Karena kita sudah join tabel jurnal di Resource query
                        return $query->orderBy('jurnal.tanggal', $direction);
                    }),

                // No. Jurnal (dari header jurnal)
                TextColumn::make('jurnal.nomor_jurnal')
                    ->label('No. Jurnal')
                    ->alignCenter()
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('jurnal.nomor_jurnal', $direction);
                    }),

                // Ref (kode transaksi: PBL / PJL / BEB / dll)
                TextColumn::make('jurnal.referensi_transaksi')
                    ->label('Ref')
                    ->searchable()
                    ->wrap(),

                // Akun (kode — nama)
                TextColumn::make('akun_display')
                    ->label('Akun')
                    ->wrap()
                    ->state(function ($record): string {
                        $kode = $record->akun?->kode_akun ?? '-';
                        $nama = $record->akun?->nama_akun ?? '-';
                        return "{$kode} — {$nama}";
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        // Search ke tabel akun via join implisit relasi
                        return $query->whereHas('akun', function (Builder $q) use ($search) {
                            $q->where('kode_akun', 'like', "%{$search}%")
                                ->orWhere('nama_akun', 'like', "%{$search}%");
                        });
                    }),

                // Debit
                TextColumn::make('debit')
                    ->label('Debit')
                    ->alignEnd()
                    ->formatStateUsing(function ($state): string {
                        $val = (float) $state;
                        if ($val <= 0) return '-';
                        return 'Rp ' . number_format($val, 0, ',', '.');
                    }),

                // Credit (kredit)
                TextColumn::make('kredit')
                    ->label('Credit')
                    ->alignEnd()
                    ->formatStateUsing(function ($state): string {
                        $val = (float) $state;
                        if ($val <= 0) return '-';
                        return 'Rp ' . number_format($val, 0, ',', '.');
                    }),
            ])
            ->filters([
                // Filter akun (COA)
                SelectFilter::make('akun_id')
                    ->label('Akun')
                    ->options(function () {
                        return Akun::query()
                            ->where('is_aktif', true)
                            ->orderBy('kode_akun')
                            ->get()
                            ->mapWithKeys(fn ($a) => [
                                $a->id => "{$a->kode_akun} — {$a->nama_akun}",
                            ])
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),

                // Filter tanggal range (berdasarkan jurnal.tanggal)
                Filter::make('tanggal_range')
                    ->label('Periode')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, function (Builder $q, $from) {
                                // Karena query sudah join ke jurnal
                                return $q->whereDate('jurnal.tanggal', '>=', $from);
                            })
                            ->when($data['until'] ?? null, function (Builder $q, $until) {
                                return $q->whereDate('jurnal.tanggal', '<=', $until);
                            });
                    }),
            ])
            ->recordActions([
                // Read-only: kosongin biar gak ada edit
            ])
            ->toolbarActions([
                // Read-only: kosongin biar gak ada bulk delete
            ])
            ->striped()
            ->paginated([25, 50, 100]);
    }
}

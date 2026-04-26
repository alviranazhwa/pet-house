<?php

namespace App\Filament\Resources\JurnalDetails;

use App\Filament\Resources\JurnalDetails\Pages\ListJurnalDetails;
use App\Filament\Resources\JurnalDetails\Tables\JurnalDetailsTable;
use App\Models\JurnalDetail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JurnalDetailResource extends Resource
{
    protected static ?string $model = JurnalDetail::class;

    // Icon & Group sesuai request kamu
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static \UnitEnum|string|null $navigationGroup = 'Laporan Keuangan';

    // Biar kebaca sebagai "Jurnal Umum"
    protected static ?string $navigationLabel = 'Jurnal Umum';
    protected static ?string $modelLabel = 'Jurnal Umum';
    protected static ?string $pluralModelLabel = 'Jurnal Umum';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * IMPORTANT:
     * - Join ke tabel jurnal buat sorting default yang stabil (tanggal + nomor_jurnal)
     * - Eager load relasi biar gak N+1
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select('jurnal_detail.*')
            ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
            ->with(['jurnal', 'akun'])
            ->orderBy('jurnal.tanggal', 'desc')
            ->orderBy('jurnal.nomor_jurnal', 'desc')
            ->orderBy('jurnal_detail.id', 'asc');
    }

    public static function table(Table $table): Table
    {
        return JurnalDetailsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJurnalDetails::route('/'),
        ];
    }
}

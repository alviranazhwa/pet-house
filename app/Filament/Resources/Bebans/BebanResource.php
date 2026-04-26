<?php

namespace App\Filament\Resources\Bebans;

use App\Filament\Resources\Bebans\Pages\CreateBeban;
use App\Filament\Resources\Bebans\Pages\EditBeban;
use App\Filament\Resources\Bebans\Pages\ListBebans;
use App\Filament\Resources\Bebans\Schemas\BebanForm;
use App\Filament\Resources\Bebans\Tables\BebansTable;
use App\Models\Jurnal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BebanResource extends Resource
{
    protected static ?string $model = Jurnal::class;

    // Sidebar
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ReceiptPercent;
    protected static \UnitEnum|string|null $navigationGroup = 'Laporan Keuangan';
    protected static ?string $navigationLabel = 'Input Beban';
    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'Input Beban';
    protected static ?string $pluralModelLabel = 'Input Beban';

    /**
     * Filter khusus transaksi BEBAN aja.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('sumber_transaksi', 'BEBAN')
            ->with(['detail.akun'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('nomor_jurnal', 'desc');
    }

    public static function form(Schema $schema): Schema
    {
        return BebanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BebansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBebans::route('/'),
            'create' => CreateBeban::route('/create'),
            'edit'   => EditBeban::route('/{record}/edit'),
        ];
    }
}

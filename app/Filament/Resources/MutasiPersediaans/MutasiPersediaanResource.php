<?php

namespace App\Filament\Resources\MutasiPersediaans;

use App\Filament\Resources\MutasiPersediaans\Pages\ListMutasiPersediaans;
use App\Filament\Resources\MutasiPersediaans\Tables\MutasiPersediaansTable;
use App\Models\MutasiPersediaan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MutasiPersediaanResource extends Resource
{
    protected static ?string $model = MutasiPersediaan::class;

    // Icon diganti (sesuai contoh yang kamu kasih)
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowsRightLeft;

    protected static \UnitEnum|string|null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Mutasi Persediaan';
    protected static ?int $navigationSort = 40;

    // NOTE: read-only → kita nggak pakai form sama sekali.

    public static function table(Table $table): Table
    {
        return MutasiPersediaansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMutasiPersediaans::route('/'),
        ];
    }
}

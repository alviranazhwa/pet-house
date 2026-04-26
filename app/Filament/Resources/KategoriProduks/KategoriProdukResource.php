<?php

namespace App\Filament\Resources\KategoriProduks;

use App\Filament\Resources\KategoriProduks\Pages\CreateKategoriProduk;
use App\Filament\Resources\KategoriProduks\Pages\EditKategoriProduk;
use App\Filament\Resources\KategoriProduks\Pages\ListKategoriProduks;
use App\Filament\Resources\KategoriProduks\Schemas\KategoriProdukForm;
use App\Filament\Resources\KategoriProduks\Tables\KategoriProduksTable;
use App\Models\KategoriProduk;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KategoriProdukResource extends Resource
{
    protected static ?string $model = KategoriProduk::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';
    protected static \UnitEnum|string|null $navigationGroup = 'Master Data';
    protected static ?string $recordTitleAttribute = 'Kategori Produk';
    protected static ?string $navigationLabel = 'Kategori Produk';

    public static function form(Schema $schema): Schema
    {
        return KategoriProdukForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KategoriProduksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKategoriProduks::route('/'),
            'create' => CreateKategoriProduk::route('/create'),
            'edit' => EditKategoriProduk::route('/{record}/edit'),
        ];
    }
}

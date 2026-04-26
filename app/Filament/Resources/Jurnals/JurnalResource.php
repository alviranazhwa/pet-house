<?php

namespace App\Filament\Resources\Jurnals;

use App\Filament\Resources\Jurnals\Pages\CreateJurnal;
use App\Filament\Resources\Jurnals\Pages\EditJurnal;
use App\Filament\Resources\Jurnals\Pages\ListJurnals;
use App\Filament\Resources\Jurnals\RelationManagers\JurnalDetailsRelationManager;
use App\Filament\Resources\Jurnals\Schemas\JurnalForm;
use App\Filament\Resources\Jurnals\Tables\JurnalsTable;
use App\Models\Jurnal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Resources\Jurnals\Pages\JurnalUmum;
use App\Filament\Resources\Jurnals\Pages\ViewJurnal;



class JurnalResource extends Resource
{
    protected static ?string $model = Jurnal::class;
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';
    protected static \UnitEnum|string|null $navigationGroup = 'Laporan Keuangan';
    protected static ?string $navigationLabel = 'Jurnal Umum';
    protected static ?string $recordTitleAttribute = 'nomor_jurnal';

    public static function form(Schema $schema): Schema
    {
        return JurnalForm::configure($schema);
    }

    /**
     * Table header default (dipakai kalau kamu nanti butuh “header mode” lagi).
     * Untuk sekarang, ListJurnals akan kita override view jadi “Jurnal Umum”.
     */
    public static function table(Table $table): Table
    {
        return JurnalsTable::configure($table);
    }

    // public static function getRelations(): array
    // {
    //     return [
    //         JurnalDetailsRelationManager::class,
    //     ];
    // }

    public static function getPages(): array
    {
        return [
        'index'  => ListJurnals::route('/'),
        'create' => CreateJurnal::route('/create'),
        'view'   => ViewJurnal::route('/{record}'),        // ✅ ini kuncinya
        'edit'   => EditJurnal::route('/{record}/edit'),
        ];
    }
}

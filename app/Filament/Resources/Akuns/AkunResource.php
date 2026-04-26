<?php

namespace App\Filament\Resources\Akuns;

use App\Filament\Resources\Akuns\Pages\CreateAkun;
use App\Filament\Resources\Akuns\Pages\EditAkun;
use App\Filament\Resources\Akuns\Pages\ListAkuns;
use App\Filament\Resources\Akuns\Schemas\AkunForm;
use App\Filament\Resources\Akuns\Tables\AkunsTable;
use App\Models\Akun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AkunResource extends Resource
{
    protected static ?string $model = Akun::class;

    // ICON COA (Chart of Accounts)
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    // GROUP SIDEBAR
    protected static \UnitEnum|string|null $navigationGroup = 'Master Data';

    // TITLE RECORD
    protected static ?string $recordTitleAttribute = 'nama';

    public static function getNavigationLabel(): string
    {
        return 'Akun (COA)';
    }

    public static function getPluralLabel(): string
    {
        return 'Akun (COA)';
    }

    public static function form(Schema $schema): Schema
    {
        return AkunForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AkunsTable::configure($table);
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
            'index'  => ListAkuns::route('/'),
            'create' => CreateAkun::route('/create'),
            'edit'   => EditAkun::route('/{record}/edit'),
        ];
    }
}

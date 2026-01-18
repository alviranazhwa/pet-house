<?php

namespace App\Filament\Resources\JurnalDetails\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class JurnalDetailForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('jurnal_id')
                    ->required()
                    ->numeric(),
                TextInput::make('akun_id')
                    ->required()
                    ->numeric(),
                TextInput::make('debit')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('kredit')
                    ->required()
                    ->numeric()
                    ->default(0.0),
            ]);
    }
}

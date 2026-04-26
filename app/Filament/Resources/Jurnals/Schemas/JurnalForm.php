<?php

namespace App\Filament\Resources\Jurnals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class JurnalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nomor_jurnal')
                    ->required(),
                DatePicker::make('tanggal')
                    ->required(),
                TextInput::make('sumber_transaksi')
                    ->required(),
                TextInput::make('referensi_transaksi')
                    ->required(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
            ]);
    }
}

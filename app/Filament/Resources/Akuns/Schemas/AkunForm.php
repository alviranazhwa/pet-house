<?php

namespace App\Filament\Resources\Akuns\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AkunForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kode_akun')
                    ->required(),
                TextInput::make('nama_akun')
                    ->required(),
                Select::make('kategori')
                    ->options([
            'aset' => 'Aset',
            'kewajiban' => 'Kewajiban',
            'modal' => 'Modal',
            'pendapatan' => 'Pendapatan',
            'beban' => 'Beban',
        ])
                    ->required(),
                Select::make('posisi_saldo')
                    ->options(['debit' => 'Debit', 'kredit' => 'Kredit'])
                    ->required(),
                Toggle::make('is_aktif')
                    ->required(),
            ]);
    }
}

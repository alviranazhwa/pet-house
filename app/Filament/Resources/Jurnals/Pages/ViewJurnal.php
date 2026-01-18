<?php

namespace App\Filament\Resources\Jurnals\Pages;

use App\Filament\Resources\Jurnals\JurnalResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewJurnal extends ViewRecord
{
    protected static string $resource = JurnalResource::class;

    // Biar tombol Edit gak nongol, view-only vibes
    protected function getHeaderActions(): array
    {
        return [
            // kalau kamu mau, bisa kasih tombol balik/print nanti
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(JurnalResource::getUrl('index')),
        ];
    }
}

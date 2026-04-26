<?php

namespace App\Filament\Resources\MutasiPersediaans\Pages;

use App\Filament\Resources\MutasiPersediaans\MutasiPersediaanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMutasiPersediaans extends ListRecords
{
    protected static string $resource = MutasiPersediaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\MutasiPersediaans\Pages;

use App\Filament\Resources\MutasiPersediaans\MutasiPersediaanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditMutasiPersediaan extends EditRecord
{
    protected static string $resource = MutasiPersediaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

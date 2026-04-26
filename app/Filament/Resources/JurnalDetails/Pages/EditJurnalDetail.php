<?php

namespace App\Filament\Resources\JurnalDetails\Pages;

use App\Filament\Resources\JurnalDetails\JurnalDetailResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJurnalDetail extends EditRecord
{
    protected static string $resource = JurnalDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

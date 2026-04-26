<?php

namespace App\Filament\Resources\Bebans\Pages;

use App\Filament\Resources\Bebans\BebanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBeban extends EditRecord
{
    protected static string $resource = BebanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

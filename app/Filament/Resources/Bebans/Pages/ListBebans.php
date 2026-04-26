<?php

namespace App\Filament\Resources\Bebans\Pages;

use App\Filament\Resources\Bebans\BebanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBebans extends ListRecords
{
    protected static string $resource = BebanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Input Beban'),
        ];
    }
}

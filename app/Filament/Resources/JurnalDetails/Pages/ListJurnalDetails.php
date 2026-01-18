<?php

namespace App\Filament\Resources\JurnalDetails\Pages;

use App\Filament\Resources\JurnalDetails\JurnalDetailResource;
use Filament\Resources\Pages\ListRecords;

class ListJurnalDetails extends ListRecords
{
    protected static string $resource = JurnalDetailResource::class;

    protected function getHeaderActions(): array
    {
        // Read-only: biar aman & clean (dosen tinggal baca)
        return [];
    }
}

<?php

namespace App\Filament\Resources\ClosureDayResource\Pages;

use App\Filament\Resources\ClosureDayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClosureDays extends ListRecords
{
    protected static string $resource = ClosureDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuovo'),
        ];
    }
}

<?php

namespace App\Filament\Resources\CoursePurchaseResource\Pages;

use App\Filament\Resources\CoursePurchaseResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewCoursePurchase extends ViewRecord
{
    protected static string $resource = CoursePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

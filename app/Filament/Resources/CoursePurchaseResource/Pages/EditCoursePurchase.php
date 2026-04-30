<?php

namespace App\Filament\Resources\CoursePurchaseResource\Pages;

use App\Filament\Resources\CoursePurchaseResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditCoursePurchase extends EditRecord
{
    protected static string $resource = CoursePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

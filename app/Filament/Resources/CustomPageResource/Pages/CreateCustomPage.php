<?php

namespace App\Filament\Resources\CustomPageResource\Pages;

use App\Filament\Resources\CustomPageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCustomPage extends CreateRecord
{
    protected static string $resource = CustomPageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

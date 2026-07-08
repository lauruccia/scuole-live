<?php

namespace App\Filament\Resources\NewsPostResource\Pages;

use App\Filament\Resources\NewsPostResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateNewsPost extends CreateRecord
{
    protected static string $resource = NewsPostResource::class;

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

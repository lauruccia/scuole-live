<?php

namespace App\Filament\Teacher\Resources\TeacherHomeworkResource\Pages;

use App\Filament\Teacher\Resources\TeacherHomeworkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacherHomework extends EditRecord
{
    protected static string $resource = TeacherHomeworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

<?php

namespace App\Filament\Teacher\Resources\TeacherHomeworkResource\Pages;

use App\Filament\Teacher\Resources\TeacherHomeworkResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTeacherHomework extends CreateRecord
{
    protected static string $resource = TeacherHomeworkResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['teacher_id'] = Auth::id();
        unset($data['student_search']); // campo virtuale, non su DB
        if (! empty($data['attachment_path'])) {
            $data['attachment_name'] = basename($data['attachment_path']);
        }
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

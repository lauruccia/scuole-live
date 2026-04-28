<?php

namespace App\Filament\Resources\HomeworkResource\Pages;

use App\Filament\Resources\HomeworkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHomework extends EditRecord
{
    protected static string $resource = HomeworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Precompila il campo di ricerca studente con lo studente del contratto
        if (! empty($data['contract_id'])) {
            $student = \App\Models\Contract::find($data['contract_id'])
                ?->students()
                ->first();
            if ($student) {
                $data['student_search'] = $student->id;
            }
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['student_search']);
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

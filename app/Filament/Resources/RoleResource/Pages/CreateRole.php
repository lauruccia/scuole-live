<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ✅ niente di speciale: le permissions le syncamo dopo
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncPermissions();
    }

    private function syncPermissions(): void
    {
        $state = $this->form->getState();

        $all = array_merge(
            $state['permissions_report'] ?? [],
            $state['permissions_studenti'] ?? [],
            $state['permissions_didattica'] ?? [],
            $state['permissions_risorse'] ?? [],
            $state['permissions_impostazioni'] ?? [],
            $state['permissions_widget'] ?? [],
        );

        $all = array_values(array_unique(Arr::flatten($all)));

        $this->record->syncPermissions($all);
    }
}

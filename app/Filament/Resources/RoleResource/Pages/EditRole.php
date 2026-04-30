<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $names = $this->record->permissions()->pluck('name')->toArray();

        // ✅ distribuisci nei tab (semplice: li metti tutti, tanto poi filtrano le options)
        $data['permissions_report'] = $names;
        $data['permissions_studenti'] = $names;
        $data['permissions_didattica'] = $names;
        $data['permissions_risorse'] = $names;
        $data['permissions_impostazioni'] = $names;
        $data['permissions_widget'] = $names;

        return $data;
    }

    protected function afterSave(): void
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

<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Permission;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset(
            $data['extra_permissions_studenti'],
            $data['extra_permissions_didattica'],
            $data['extra_permissions_risorse'],
            $data['extra_permissions_impostazioni'],
            $data['extra_permissions_widget'],
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $state = $this->form->getState();

        $names = array_values(array_unique(array_merge(
            $state['extra_permissions_studenti'] ?? [],
            $state['extra_permissions_didattica'] ?? [],
            $state['extra_permissions_risorse'] ?? [],
            $state['extra_permissions_impostazioni'] ?? [],
            $state['extra_permissions_widget'] ?? [],
        )));

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $names)
            ->get();

        // permessi extra = direct permissions (non da ruolo)
        $this->record->syncPermissions($permissions);
    }
}

<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $names = $this->record->permissions()->pluck('name')->toArray();

        $data['extra_permissions_studenti'] = $this->filterByResources($names, ['student', 'contract', 'enrollment', 'installment']);
        $data['extra_permissions_didattica'] = $this->filterByResources($names, ['course', 'lesson', 'closure_day', 'closure::day']);
        $data['extra_permissions_risorse'] = $this->filterByResources($names, ['teacher']);
        $data['extra_permissions_impostazioni'] = $this->filterByResources($names, ['user', 'role', 'permission']);
        $data['extra_permissions_widget'] = array_values(array_filter($names, fn ($n) => str_starts_with($n, 'widget_')));

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function afterSave(): void
    {
        $state = $this->form->getState();

        $names = array_values(array_unique(array_merge(
            $state['extra_permissions_studenti'] ?? [],
            $state['extra_permissions_didattica'] ?? [],
            $state['extra_permissions_risorse'] ?? [],
            $state['extra_permissions_impostazioni'] ?? [],
            $state['extra_permissions_widget'] ?? [],
        )));

        // Direct permissions (extra)
        $this->record->syncPermissions($names);
    }

    private function filterByResources(array $permissionNames, array $resources): array
    {
        $resources = array_map(fn ($r) => str_replace('::', '_', Str::snake($r)), $resources);

        return array_values(array_filter($permissionNames, function ($name) use ($resources) {
            if (str_starts_with($name, 'widget_')) {
                return false;
            }

            $res = $name;
            foreach ([
                'force_delete_any_',
                'force_delete_',
                'restore_any_',
                'restore_',
                'delete_any_',
                'delete_',
                'update_',
                'create_',
                'view_any_',
                'view_',
                'replicate_',
                'reorder_',
            ] as $prefix) {
                if (str_starts_with($res, $prefix)) {
                    $res = substr($res, strlen($prefix));
                    break;
                }
            }

            $res = Str::snake(str_replace('::', '_', $res));

            return in_array($res, $resources, true);
        }));
    }
}

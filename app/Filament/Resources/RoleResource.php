<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

use App\Models\Role;


class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Ruoli';
    protected static ?string $modelLabel = 'Ruolo';
    protected static ?string $pluralModelLabel = 'Ruoli';

    protected static ?string $navigationGroup = 'Impostazioni';
    protected static ?int $navigationSort = 999;

    public static function shouldRegisterNavigation(): bool
    {
        $u = auth()->user();
        if (! $u) return false;

        return $u->hasAnyRole(['superadmin', 'super_admin']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dati ruolo')->schema([
                TextInput::make('name')
                    ->label('Nome ruolo')
                    ->required()
                    ->maxLength(255),
            ]),

            Section::make('Permessi')
                ->description('Seleziona cosa può fare questo ruolo nel sistema.')
                ->schema([
                    Tabs::make('PermessiTabs')->tabs([

                        Tab::make('Report')->schema([
                            CheckboxList::make('permissions_report')
                                ->label('Permessi Report')
                                ->options(fn () => static::pagePermissionsFor(['StudentHoursReport', 'TeacherHoursReport']))
                                ->columns(2)
                                ->bulkToggleable()
                                ->searchable(),
                        ]),

                        Tab::make('Studenti')->schema([
                            CheckboxList::make('permissions_studenti')
                                ->label('Permessi Studenti')
                                ->options(fn () => static::permissionsForResources([
                                    'student', 'contract', 'enrollment', 'installment',
                                ]))
                                ->columns(3)
                                ->bulkToggleable()
                                ->searchable(),
                        ]),

                        Tab::make('Didattica')->schema([
                            CheckboxList::make('permissions_didattica')
                                ->label('Permessi Didattica')
                                ->options(fn () => array_merge(
                                    static::permissionsForResources(['course', 'lesson', 'closure_day', 'closure::day']),
                                    // ✅ include la page calendario
                                    static::pagePermissionsFor(['LessonCalendar'])
                                ))
                                ->columns(3)
                                ->bulkToggleable()
                                ->searchable(),
                        ]),

                        Tab::make('Risorse umane')->schema([
                            CheckboxList::make('permissions_risorse')
                                ->label('Permessi Risorse umane')
                                ->options(fn () => static::permissionsForResources(['teacher']))
                                ->columns(3)
                                ->bulkToggleable()
                                ->searchable(),
                        ]),

                        Tab::make('Impostazioni')->schema([
                            CheckboxList::make('permissions_impostazioni')
                                ->label('Permessi Impostazioni')
                                ->options(fn () => static::permissionsForResources(['user', 'role', 'permission']))
                                ->columns(3)
                                ->bulkToggleable()
                                ->searchable(),
                        ]),

                        Tab::make('Widget')->schema([
                            CheckboxList::make('permissions_widget')
                                ->label('Widget')
                                ->options(fn () => static::widgetPermissions())
                                ->columns(2)
                                ->bulkToggleable()
                                ->searchable(),
                        ]),
                    ]),
                ]),
        ]);
    }

    protected static function pagePermissionsFor(array $contains): array
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'like', 'page_%')
            ->where(function ($q) use ($contains) {
                foreach ($contains as $term) {
                    $q->orWhere('name', 'like', "%{$term}%");
                }
            })
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Permission $p) => [$p->name => static::prettyPermissionLabel($p->name)])
            ->toArray();
    }

    protected static function permissionsForResources(array $resources): array
    {
        $resources = array_map(fn ($r) => str_replace('::', '_', Str::snake($r)), $resources);

        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->filter(function (Permission $p) use ($resources) {
                $res = static::permissionResource($p->name);
                return in_array($res, $resources, true);
            })
            ->mapWithKeys(fn (Permission $p) => [$p->name => static::prettyPermissionLabel($p->name)])
            ->toArray();
    }

    protected static function widgetPermissions(): array
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'like', 'widget_%')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Permission $p) => [
                $p->name => 'Visualizza widget — ' . Str::of($p->name)->after('widget_')->replace('_', ' ')->title()->toString(),
            ])
            ->toArray();
    }

    protected static function permissionResource(string $name): string
    {
        if (str_starts_with($name, 'widget_')) return 'widget';
        if (str_starts_with($name, 'page_')) return 'page';

        foreach ([
            'force_delete_any_','force_delete_','restore_any_','restore_','delete_any_','delete_',
            'update_','create_','view_any_','view_','replicate_','reorder_',
        ] as $prefix) {
            if (str_starts_with($name, $prefix)) {
                $name = substr($name, strlen($prefix));
                break;
            }
        }

        return Str::snake(str_replace('::', '_', $name));
    }

    protected static function prettyPermissionLabel(string $name): string
    {
        if (str_starts_with($name, 'page_')) {
            return 'Accedi pagina — ' . Str::of($name)->after('page_')->replace('_', ' ')->title()->toString();
        }

        $mapActions = [
            'view_any' => 'Visualizza elenco',
            'view' => 'Visualizza',
            'create' => 'Crea',
            'update' => 'Modifica',
            'delete' => 'Elimina',
            'delete_any' => 'Elimina (tutti)',
            'restore' => 'Ripristina',
            'restore_any' => 'Ripristina (tutti)',
            'force_delete' => 'Eliminazione definitiva',
            'force_delete_any' => 'Eliminazione definitiva (tutti)',
            'replicate' => 'Duplica',
            'reorder' => 'Riordina',
        ];

        if (str_starts_with($name, 'widget_')) {
            return 'Visualizza widget — ' . Str::of($name)->after('widget_')->replace('_', ' ')->title()->toString();
        }

        $action = null;
        $resource = $name;

        foreach (array_keys($mapActions) as $a) {
            $prefix = $a . '_';
            if (str_starts_with($name, $prefix)) {
                $action = $a;
                $resource = substr($name, strlen($prefix));
                break;
            }
        }

        $resource = Str::of(str_replace('::', '_', $resource))->replace('_', ' ')->title()->toString();
        $actionLabel = $mapActions[$action] ?? Str::of((string) $action)->replace('_', ' ')->title()->toString();

        return "{$actionLabel} — {$resource}";
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome ruolo')->searchable()->sortable(),
                TextColumn::make('guard_name')->label('Guard')->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifica'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Elimina'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}

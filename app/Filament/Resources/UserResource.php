<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Hash;
use Filament\Facades\Filament;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Users';
    protected static ?string $modelLabel = 'User';
    protected static ?string $pluralModelLabel = 'Users';
    protected static ?string $navigationGroup = 'Impostazioni';
   protected static ?int $navigationSort = 999;

    /**
     * Dashboard docente pulita: il docente non vede "Users".
     */
public static function shouldRegisterNavigation(): bool
{
    $u = Filament::auth()->user();

    return ! $u || ! $u->hasRole('Docente');
}

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->maxLength(255),

            TextInput::make('password')
                ->label('Password')
                ->password()
                ->revealable()
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $context) => $context === 'create')
                ->minLength(8)
                ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null),



Select::make('roles')
    ->label('Ruolo')
    ->relationship('roles', 'name')
    ->options(function () {
        $u = auth()->user();

        $q = Role::query()->orderBy('name');

        // chi non è superadmin non può assegnare superadmin
        if ($u && ! $u->hasRole('superadmin')) {
            $q->where('name', '!=', 'superadmin');
        }

        return $q->pluck('name', 'id')->toArray();
    })
    ->preload()
    ->searchable()
    ->required()
    ->multiple(false),

            Tabs::make('PermessiExtraTabs')
    ->tabs([
        Tab::make('Studenti')->schema([
            CheckboxList::make('extra_permissions_studenti')
                ->label('Permessi extra Studenti')
                ->options(fn () => self::permissionsForResources(['student', 'contract', 'enrollment', 'installment']))
                ->columns(2)
                ->bulkToggleable()
                ->searchable(),
        ]),
        Tab::make('Didattica')->schema([
            CheckboxList::make('extra_permissions_didattica')
                ->label('Permessi extra Didattica')
                ->options(fn () => self::permissionsForResources(['course', 'lesson', 'closure_day', 'closure::day']))
                ->columns(2)
                ->bulkToggleable()
                ->searchable(),
        ]),
        Tab::make('Risorse umane')->schema([
            CheckboxList::make('extra_permissions_risorse')
                ->label('Permessi extra Risorse umane')
                ->options(fn () => self::permissionsForResources(['teacher']))
                ->columns(2)
                ->bulkToggleable()
                ->searchable(),
        ]),
        Tab::make('Impostazioni')->schema([
            CheckboxList::make('extra_permissions_impostazioni')
                ->label('Permessi extra Impostazioni')
                ->options(fn () => self::permissionsForResources(['user', 'role', 'permission']))
                ->columns(2)
                ->bulkToggleable()
                ->searchable(),
        ]),
        Tab::make('Widget')->schema([
            CheckboxList::make('extra_permissions_widget')
                ->label('Widget')
                ->options(fn () => self::widgetPermissions())
                ->columns(2)
                ->bulkToggleable()
                ->searchable(),
        ]),
    ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->sortable(),
                TextColumn::make('roles.name')->label('Ruolo')->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifica'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }


    protected static function permissionsForResources(array $resources): array
{
    $resources = array_map(fn ($r) => str_replace('::', '_', Str::snake($r)), $resources);

    $perms = Permission::query()
        ->where('guard_name', 'web')
        ->orderBy('name')
        ->get()
        ->filter(function (Permission $p) use ($resources) {
            $res = static::permissionResource($p->name);
            return in_array($res, $resources, true);
        });

    return $perms->mapWithKeys(fn (Permission $p) => [
        $p->name => static::prettyPermissionLabel($p->name),
    ])->toArray();
}

protected static function widgetPermissions(): array
{
    return Permission::query()
        ->where('guard_name', 'web')
        ->where('name', 'like', 'widget\_%')
        ->orderBy('name')
        ->get()
        ->mapWithKeys(fn (Permission $p) => [
            $p->name => 'Visualizza ' . Str::of($p->name)->after('widget_')->replace('_', ' ')->toString(),
        ])->toArray();
}

protected static function permissionResource(string $name): string
{
    if (str_starts_with($name, 'widget_')) {
        return 'widget';
    }

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
        if (str_starts_with($name, $prefix)) {
            $name = substr($name, strlen($prefix));
            break;
        }
    }

    return Str::snake(str_replace('::', '_', $name));
}

protected static function prettyPermissionLabel(string $name): string
{
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
        return 'Visualizza ' . Str::of($name)->after('widget_')->replace('_', ' ')->toString();
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

public static function getEloquentQuery(): Builder
{
    $q = parent::getEloquentQuery();

    $u = auth()->user();
    if ($u && ! $u->hasRole('superadmin')) {
        // non mostrare utenti superadmin
        $q->whereDoesntHave('roles', fn ($r) => $r->where('name', 'superadmin'));
    }

    return $q;
}

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }


}

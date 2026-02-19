<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Models\Permission;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Permessi';
    protected static ?string $modelLabel = 'Permesso';
    protected static ?string $pluralModelLabel = 'Permessi';

    protected static ?string $navigationGroup = 'Impostazioni';
protected static ?int $navigationSort = 999;

    public static function shouldRegisterNavigation(): bool
{
    $u = auth()->user();
    if (! $u) return false;

    return $u->hasRole('superadmin'); // solo superadmin
}

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        return (bool) $u && $u->hasRole('superadmin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // se vuoi gestirli da UI, aggiungiamo dopo
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Nome')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('guard_name')
                ->label('Guard')
                ->badge(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Creato')
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit'   => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}

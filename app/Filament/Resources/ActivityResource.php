<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Audit log';
    protected static ?string $modelLabel = 'Voce di audit';
    protected static ?string $pluralModelLabel = 'Audit log';
    protected static ?string $navigationGroup = 'Impostazioni';
    protected static ?int $navigationSort = 998;
    protected static ?string $recordTitleAttribute = 'description';

    /* ---------------------------------------------------------------
     |  ACCESS CONTROL
     | --------------------------------------------------------------- */

    public static function shouldRegisterNavigation(): bool
    {
        $u = auth()->user();
        if (! $u) return false;

        return $u->hasAnyRole(['Superadmin', 'superadmin', 'super_admin', 'Amministrazione']);
    }

    public static function canViewAny(): bool
    {
        return self::shouldRegisterNavigation();
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    /* ---------------------------------------------------------------
     |  FORM (non usata: read-only)
     | --------------------------------------------------------------- */

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    /* ---------------------------------------------------------------
     |  TABLE
     | --------------------------------------------------------------- */

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Quando')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('log_name')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'gdpr'        => 'warning',
                        'payments'    => 'success',
                        'contracts'   => 'info',
                        'permissions' => 'danger',
                        'users'       => 'gray',
                        default       => 'primary',
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('event')
                    ->label('Evento')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'gray',
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrizione')
                    ->wrap()
                    ->searchable()
                    ->limit(80),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Tipo entità')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID entità')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Eseguito da')
                    ->formatStateUsing(function ($record) {
                        $causer = $record->causer;
                        if (! $causer) return '—';
                        return $causer->name
                            ?? trim(($causer->first_name ?? '') . ' ' . ($causer->last_name ?? ''))
                            ?: ($causer->email ?? "User #{$causer->id}");
                    })
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('batch_uuid')
                    ->label('Batch')
                    ->limit(8)
                    ->tooltip(fn ($record) => $record->batch_uuid)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('log_name')
                    ->label('Categoria')
                    ->options([
                        'gdpr'        => 'Dati personali (GDPR)',
                        'payments'    => 'Pagamenti',
                        'contracts'   => 'Contratti',
                        'users'       => 'Utenti',
                        'permissions' => 'Ruoli/Permessi',
                        'default'     => 'Default',
                    ]),

                Filters\SelectFilter::make('event')
                    ->label('Evento')
                    ->options([
                        'created' => 'Creazione',
                        'updated' => 'Modifica',
                        'deleted' => 'Eliminazione',
                    ]),

                Filters\SelectFilter::make('subject_type')
                    ->label('Tipo entità')
                    ->options(function (): array {
                        return Activity::query()
                            ->select('subject_type')
                            ->whereNotNull('subject_type')
                            ->distinct()
                            ->pluck('subject_type')
                            ->mapWithKeys(fn ($t) => [$t => class_basename($t)])
                            ->toArray();
                    }),

                Filters\Filter::make('causer_id')
                    ->label('ID utente che ha agito')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('causer_id')
                            ->label('ID utente')
                            ->numeric(),
                    ])
                    ->query(fn (Builder $q, array $data) =>
                        $q->when($data['causer_id'] ?? null, fn ($qq, $v) => $qq->where('causer_id', $v))
                    ),

                Filters\Filter::make('subject_id')
                    ->label('ID entità tracciata')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('subject_id')
                            ->label('ID entità')
                            ->numeric(),
                    ])
                    ->query(fn (Builder $q, array $data) =>
                        $q->when($data['subject_id'] ?? null, fn ($qq, $v) => $qq->where('subject_id', $v))
                    ),

                Filters\Filter::make('created_between')
                    ->label('Periodo')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dal'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Al'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        return $q
                            ->when($data['from'] ?? null, fn ($qq, $v) => $qq->whereDate('created_at', '>=', $v))
                            ->when($data['until'] ?? null, fn ($qq, $v) => $qq->whereDate('created_at', '<=', $v));
                    })
                    ->columns(2),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Dettagli'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_csv')
                        ->label('Esporta CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->action(function ($records): StreamedResponse {
                            return self::streamCsv($records);
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_filtered')
                    ->label('Esporta tutto (filtrato)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (): StreamedResponse {
                        // Esporta i risultati attualmente filtrati (max 50k righe per sicurezza)
                        $records = Activity::query()
                            ->orderBy('created_at', 'desc')
                            ->limit(50000)
                            ->get();
                        return self::streamCsv($records);
                    }),
            ])
            ->emptyStateHeading('Nessuna attività registrata')
            ->emptyStateDescription('Le modifiche su contratti, studenti, pagamenti, utenti e permessi appariranno qui.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->striped()
            ->persistFiltersInSession();
    }

    /* ---------------------------------------------------------------
     |  CSV EXPORT (GDPR-friendly)
     | --------------------------------------------------------------- */

    protected static function streamCsv($records): StreamedResponse
    {
        $filename = 'audit-log-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');
            // BOM per Excel italiano
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'id',
                'created_at',
                'log_name',
                'event',
                'subject_type',
                'subject_id',
                'causer_id',
                'causer_email',
                'description',
                'old_values',
                'new_values',
                'batch_uuid',
            ], ';');

            foreach ($records as $r) {
                $props = $r->properties ?? collect();
                $old = $props->get('old', []);
                $new = $props->get('attributes', []);

                fputcsv($out, [
                    $r->id,
                    optional($r->created_at)->format('Y-m-d H:i:s'),
                    $r->log_name,
                    $r->event,
                    $r->subject_type ? class_basename($r->subject_type) : '',
                    $r->subject_id,
                    $r->causer_id,
                    optional($r->causer)->email,
                    $r->description,
                    json_encode($old, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    $r->batch_uuid,
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /* ---------------------------------------------------------------
     |  EAGER LOAD (perf)
     | --------------------------------------------------------------- */

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['causer', 'subject']);
    }

    /* ---------------------------------------------------------------
     |  PAGES
     | --------------------------------------------------------------- */

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'view'  => Pages\ViewActivity::route('/{record}'),
        ];
    }
}

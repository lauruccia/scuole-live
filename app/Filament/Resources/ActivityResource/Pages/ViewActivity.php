<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'Voce di audit #' . $this->record->id;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Riepilogo')
                ->columns(3)
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Quando')
                        ->dateTime('d/m/Y H:i:s'),

                    TextEntry::make('log_name')
                        ->label('Categoria')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'gdpr'        => 'warning',
                            'payments'    => 'success',
                            'contracts'   => 'info',
                            'permissions' => 'danger',
                            'users'       => 'gray',
                            default       => 'primary',
                        }),

                    TextEntry::make('event')
                        ->label('Evento')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'created' => 'success',
                            'updated' => 'warning',
                            'deleted' => 'danger',
                            default   => 'gray',
                        }),

                    TextEntry::make('description')
                        ->label('Descrizione')
                        ->columnSpanFull(),

                    TextEntry::make('subject_label')
                        ->label('Entità tracciata')
                        ->state(function ($record): string {
                            if (! $record->subject_type) return '—';
                            return class_basename($record->subject_type) . ' #' . $record->subject_id;
                        }),

                    TextEntry::make('causer_label')
                        ->label('Eseguito da')
                        ->state(function ($record): string {
                            $causer = $record->causer;
                            if (! $causer) return 'Sistema / non autenticato';
                            $name = $causer->name
                                ?? trim(($causer->first_name ?? '') . ' ' . ($causer->last_name ?? ''))
                                ?: ($causer->email ?? "User #{$causer->id}");
                            return $name . ' (#' . $causer->id . ')';
                        }),

                    TextEntry::make('batch_uuid')
                        ->label('Batch UUID')
                        ->copyable()
                        ->placeholder('—'),
                ]),

            Section::make('Differenze (old → new)')
                ->description('Solo i campi effettivamente cambiati.')
                ->schema([
                    ViewEntry::make('diff')
                        ->view('filament.resources.activity.diff')
                        ->viewData(fn ($record) => [
                            'old' => data_get($record->properties, 'old', []),
                            'new' => data_get($record->properties, 'attributes', []),
                        ]),
                ]),
        ]);
    }
}

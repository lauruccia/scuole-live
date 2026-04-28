<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource\RelationManagers;
use App\Models\Lead;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon        = 'heroicon-o-funnel';
    protected static ?string $navigationGroup       = 'CRM';
    protected static ?string $navigationLabel       = 'Lead';
    protected static ?string $modelLabel            = 'Lead';
    protected static ?string $pluralModelLabel      = 'Lead';
    protected static ?int    $navigationSort        = 1;

    // ─── FORM ────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Dati contatto')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('first_name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('last_name')
                        ->label('Cognome')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Telefono')
                        ->tel()
                        ->maxLength(50),
                ]),

            Forms\Components\Section::make('Interesse')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('course_interest')
                        ->label('Corso di interesse')
                        ->maxLength(255)
                        ->nullable()
                        ->helperText('Inserisci il nome del corso (es. Inglese B2, Spagnolo base…)'),

                    Forms\Components\Select::make('source')
                        ->label('Fonte')
                        ->options(Lead::SOURCES)
                        ->required()
                        ->default('manual'),

                    Forms\Components\Textarea::make('interest_notes')
                        ->label('Note interesse')
                        ->columnSpanFull()
                        ->rows(2),
                ]),

            Forms\Components\Section::make('Pipeline')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Stato')
                        ->options(Lead::STATUSES)
                        ->required()
                        ->default('new'),

                    Forms\Components\Select::make('assigned_to')
                        ->label('Assegnato a')
                        ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),

                    Forms\Components\DatePicker::make('followup_at')
                        ->label('Prossimo follow-up')
                        ->nullable(),

                    Forms\Components\Textarea::make('loss_reason')
                        ->label('Motivo perdita')
                        ->visible(fn (Forms\Get $get) => $get('status') === 'lost')
                        ->columnSpanFull()
                        ->rows(2),
                ]),

            Forms\Components\Section::make('Note interne')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ─── TABLE ───────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nome')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name'])
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefono')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('course_interest')
                    ->label('Corso')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')
                    ->formatStateUsing(fn (string $state) => Lead::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'new'           => 'gray',
                        'contacted'     => 'info',
                        'proposal_sent' => 'warning',
                        'enrolled'      => 'success',
                        'lost'          => 'danger',
                        default         => 'gray',
                    }),

                Tables\Columns\TextColumn::make('source')
                    ->label('Fonte')
                    ->formatStateUsing(fn (string $state) => Lead::SOURCES[$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assegnato a')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('followup_at')
                    ->label('Follow-up')
                    ->date('d/m/Y')
                    ->color(fn ($record) => match (true) {
                        $record?->hasOverdueFollowup() => 'danger',
                        $record?->hasFollowupToday()   => 'warning',
                        default                        => null,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creato')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stato')
                    ->options(Lead::STATUSES),

                Tables\Filters\SelectFilter::make('source')
                    ->label('Fonte')
                    ->options(Lead::SOURCES),


                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Assegnato a')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id')),

                Tables\Filters\Filter::make('followup_overdue')
                    ->label('Follow-up scaduti')
                    ->query(fn (Builder $query) => $query->followupOverdue()),

                Tables\Filters\Filter::make('followup_today')
                    ->label('Follow-up oggi')
                    ->query(fn (Builder $query) => $query->followupToday()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ─── RELATION MANAGERS ───────────────────────────────────────────────────

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\QuotesRelationManager::class,
            RelationManagers\ActivitiesRelationManager::class,
        ];
    }

    // ─── PAGES ───────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit'   => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}

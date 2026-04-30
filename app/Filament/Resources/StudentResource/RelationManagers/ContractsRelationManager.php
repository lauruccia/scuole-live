<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Filament\Resources\ContractResource;
use App\Models\Contract;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'contracts';

    protected static ?string $title = 'Contratti';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),

                Tables\Columns\TextColumn::make('billing_display_name')
                    ->label('Intestatario')
                    ->wrap(),

                Tables\Columns\TextColumn::make('course.name')
                    ->label('Corso')
                    ->wrap(),

                Tables\Columns\TextColumn::make('hours_purchased')->label('Ore')->sortable(),
                Tables\Columns\TextColumn::make('hours_consumed')->label('Fruite')->sortable(),

                Tables\Columns\TextColumn::make('created_at')->label('Creato il')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('apri')
                    ->label('Apri')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(function (Contract $record): string {
                        $panelId = Filament::getCurrentPanel()?->getId() ?? 'admin';

                        return ContractResource::getUrl(
                            'edit',
                            ['record' => $record],
                            panel: $panelId
                        );
                    })
                    ->openUrlInNewTab(),
            ]);
    }
}

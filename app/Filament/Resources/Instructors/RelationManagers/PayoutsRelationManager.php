<?php

namespace App\Filament\Resources\Instructors\RelationManagers;

use App\Support\Money;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PayoutsRelationManager extends RelationManager
{
    protected static string $relationship = 'payouts';

    protected static ?string $title = 'Payout history';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Payout #')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state): string => Money::format($state)),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('provider_reference')
                    ->label('Provider ref')
                    ->placeholder('—'),
                TextColumn::make('batch.batch_key')
                    ->label('Batch'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}

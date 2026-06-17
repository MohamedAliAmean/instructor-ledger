<?php

namespace App\Filament\Resources\Instructors\Tables;

use App\Services\BalanceService;
use App\Support\Money;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstructorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('outstanding_balance')
                    ->label('Outstanding')
                    ->state(function ($record): string {
                        $balance = app(BalanceService::class)->getOutstandingBalance($record);

                        return Money::format($balance);
                    }),
                TextColumn::make('total_earned')
                    ->label('Total earned')
                    ->state(function ($record): string {
                        return Money::format(
                            app(BalanceService::class)->getTotalEarned($record),
                        );
                    }),
                TextColumn::make('total_paid')
                    ->label('Total paid')
                    ->state(function ($record): string {
                        return Money::format(
                            app(BalanceService::class)->getTotalPaid($record),
                        );
                    }),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}

<?php

namespace App\Filament\Resources\Instructors\Schemas;

use App\Services\BalanceService;
use App\Support\Money;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InstructorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Instructor')
                    ->schema([
                        TextEntry::make('name'),
                    ]),
                Section::make('Balance summary')
                    ->schema([
                        TextEntry::make('outstanding_balance')
                            ->label('Outstanding balance')
                            ->state(fn ($record): string => Money::format(
                                app(BalanceService::class)->getOutstandingBalance($record),
                            )),
                        TextEntry::make('total_earned')
                            ->label('Total earned')
                            ->state(fn ($record): string => Money::format(
                                app(BalanceService::class)->getTotalEarned($record),
                            )),
                        TextEntry::make('total_paid')
                            ->label('Total paid')
                            ->state(fn ($record): string => Money::format(
                                app(BalanceService::class)->getTotalPaid($record),
                            )),
                    ])
                    ->columns(3),
            ]);
    }
}

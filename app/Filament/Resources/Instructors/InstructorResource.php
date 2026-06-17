<?php

namespace App\Filament\Resources\Instructors;

use App\Filament\Resources\Instructors\Pages\ListInstructors;
use App\Filament\Resources\Instructors\Pages\ViewInstructor;
use App\Filament\Resources\Instructors\RelationManagers\PayoutsRelationManager;
use App\Filament\Resources\Instructors\Schemas\InstructorInfolist;
use App\Filament\Resources\Instructors\Tables\InstructorsTable;
use App\Models\Instructor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InstructorResource extends Resource
{
    protected static ?string $model = Instructor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Instructor Ledger';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InstructorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstructorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PayoutsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstructors::route('/'),
            'view' => ViewInstructor::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

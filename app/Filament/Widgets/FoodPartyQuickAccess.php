<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\FoodPartyResource\Pages\EditFoodParty;
use App\Models\FoodParty;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FoodPartyQuickAccess extends BaseWidget
{
    protected static ?int $sort = -10;

    protected int|string|array $columnStart = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FoodParty::whereUserId(auth()->id())
            )
            ->columns([
                TextColumn::make('id')->label('#'),
                TextColumn::make('description'),
                TextInputColumn::make('threshold')->rules(['integer', 'min:0', 'max:99']),
                ToggleColumn::make('active'),
            ])->recordUrl(fn (FoodParty $record): string => EditFoodParty::getUrl([$record->id]));
    }
}

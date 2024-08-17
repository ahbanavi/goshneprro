<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MarketPartyResource\Pages\EditMarketParty;
use App\Models\MarketParty;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MarketPartyQuickAccess extends BaseWidget
{
    protected static ?int $sort = 10;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MarketParty::whereUserId(auth()->id())
            )
            ->columns([
                TextColumn::make('id')->label('#'),
                TextColumn::make('description'),
                TextInputColumn::make('threshold')->rules(['integer', 'min:0', 'max:99']),
                ToggleColumn::make('active'),
            ])->recordUrl(fn (MarketParty $record): string => EditMarketParty::getUrl([$record->id]));
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\FoodParty;
use App\Models\MarketParty;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Food Parties', FoodParty::count()),
            Stat::make('Active Food Parties', FoodParty::active()->count()),
            Stat::make('Total Users', User::count()),
            Stat::make('Admin Users', User::where('is_admin', true)->count()),
            Stat::make('Total Market Parties', MarketParty::count()),
            Stat::make('Active Market Parties', MarketParty::active()->count()),
        ];
    }
}

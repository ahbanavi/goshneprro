<?php

namespace App\Filament\Widgets;

use App\Models\FoodParty;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = -5;

    protected int|string|array $columnStart = 2;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Food Parties', FoodParty::count()),
            Stat::make('Active Food Parties', FoodParty::active()->count()),
            Stat::make('Total Users', User::count()),
            Stat::make('Admin Users', User::where('is_admin', true)->count()),
        ];
    }

    protected function getColumns(): int
    {
        return 2;
    }
}

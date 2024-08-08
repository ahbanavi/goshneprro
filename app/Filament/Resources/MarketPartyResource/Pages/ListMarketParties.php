<?php

namespace App\Filament\Resources\MarketPartyResource\Pages;

use App\Filament\Resources\MarketPartyResource;
use App\Models\MarketParty;
use Filament\Actions\CreateAction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListMarketParties extends ListRecords
{
    protected static string $resource = MarketPartyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $arr = [
            'owned' => Tab::make()->icon('heroicon-m-user')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->where('user_id', auth()->id());
                })->badge(function () {
                    return MarketParty::where('user_id', auth()->id())->count();
                }),
        ];

        if (auth()->user()->isAdmin()) {
            $arr['all'] =
                Tab::make()->icon('heroicon-m-user-group')
                    ->badge(function () {
                        return MarketParty::all()->count();
                    });
        }

        return $arr;
    }
}

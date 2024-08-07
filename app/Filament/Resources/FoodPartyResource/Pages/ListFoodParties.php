<?php

namespace App\Filament\Resources\FoodPartyResource\Pages;

use App\Filament\Resources\FoodPartyResource;
use App\Models\FoodParty;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListFoodParties extends ListRecords
{
    protected static string $resource = FoodPartyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $arr = [
            'owned' => Tab::make()->icon('heroicon-m-user')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->where('user_id', auth()->id());
                })->badge(function () {
                    return FoodParty::where('user_id', auth()->id())->count();
                }),
        ];

        if (auth()->user()->isAdmin()) {
            $arr['all'] =
                Tab::make()->icon('heroicon-m-user-group')
                    ->badge(function () {
                        return FoodParty::all()->count();
                    });
        }

        return $arr;
    }
}

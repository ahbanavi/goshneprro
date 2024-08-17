<?php

namespace App\Filament\Resources\FoodPartyResource\Pages;

use App\Filament\Actions\ClearServiceCacheAction;
use App\Filament\Actions\RunServiceAction;
use App\Filament\Resources\FoodPartyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFoodParty extends EditRecord
{
    protected static string $resource = FoodPartyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            ClearServiceCacheAction::make(),
            RunServiceAction::make(),
        ];
    }
}

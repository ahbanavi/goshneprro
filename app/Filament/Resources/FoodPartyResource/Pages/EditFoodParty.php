<?php

namespace App\Filament\Resources\FoodPartyResource\Pages;

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
        ];
    }
}

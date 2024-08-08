<?php

namespace App\Filament\Resources\MarketPartyResource\Pages;

use App\Filament\Resources\MarketPartyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMarketParty extends EditRecord
{
    protected static string $resource = MarketPartyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

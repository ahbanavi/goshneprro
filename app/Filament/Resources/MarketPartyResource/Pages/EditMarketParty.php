<?php

namespace App\Filament\Resources\MarketPartyResource\Pages;

use App\Filament\Actions\ClearServiceCacheAction;
use App\Filament\Actions\RunServiceAction;
use App\Filament\Resources\MarketPartyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketParty extends EditRecord
{
    protected static string $resource = MarketPartyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            ClearServiceCacheAction::make(),
            RunServiceAction::make(),
        ];
    }
}

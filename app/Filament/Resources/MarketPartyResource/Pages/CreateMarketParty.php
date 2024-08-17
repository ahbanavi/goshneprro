<?php

namespace App\Filament\Resources\MarketPartyResource\Pages;

use App\Filament\Resources\MarketPartyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMarketParty extends CreateRecord
{
    protected static string $resource = MarketPartyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! isset($data['user_id'])) {
            $data['user_id'] = auth()->id();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

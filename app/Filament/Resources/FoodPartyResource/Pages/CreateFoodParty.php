<?php

namespace App\Filament\Resources\FoodPartyResource\Pages;

use App\Filament\Resources\FoodPartyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFoodParty extends CreateRecord
{
    protected static string $resource = FoodPartyResource::class;

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

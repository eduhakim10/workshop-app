<?php

namespace App\Filament\Resources\CustomerUserResource\Pages;

use App\Filament\Resources\CustomerUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerUser extends EditRecord
{
    protected static string $resource = CustomerUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

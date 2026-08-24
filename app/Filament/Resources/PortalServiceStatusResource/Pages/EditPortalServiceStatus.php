<?php

namespace App\Filament\Resources\PortalServiceStatusResource\Pages;

use App\Filament\Resources\PortalServiceStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPortalServiceStatus extends EditRecord
{
    protected static string $resource = PortalServiceStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

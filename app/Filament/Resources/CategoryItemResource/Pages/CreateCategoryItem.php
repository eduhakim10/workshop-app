<?php

namespace App\Filament\Resources\CategoryItemResource\Pages;

use App\Filament\Resources\CategoryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCategoryItem extends CreateRecord
{
    protected static string $resource = CategoryItemResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index'); // Redirect to the list page
    }
}

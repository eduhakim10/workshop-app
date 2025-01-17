<?php

namespace App\Filament\Resources\CategoryServiceResource\Pages;

use App\Filament\Resources\CategoryServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategoryServices extends ListRecords
{
    protected static string $resource = CategoryServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

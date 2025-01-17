<?php

namespace App\Filament\Resources\CategoryServiceResource\Pages;

use App\Filament\Resources\CategoryServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCategoryService extends CreateRecord
{
    protected static string $resource = CategoryServiceResource::class;
}

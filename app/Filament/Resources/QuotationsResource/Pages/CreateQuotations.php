<?php

namespace App\Filament\Resources\QuotationsResource\Pages;

use App\Filament\Resources\QuotationsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotations extends CreateRecord
{
    protected static string $resource = QuotationsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['stage'] = 1; // agar quotation masuk sebagai stage 1
            return $data;
        }
    protected function getRedirectUrl(): string
        {
            return QuotationsResource::getUrl(); // Biar balik ke halaman quotation index
        }




}


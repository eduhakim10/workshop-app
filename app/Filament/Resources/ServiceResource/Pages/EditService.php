<?php

namespace App\Filament\Resources\ServiceResource\Pages;
use Filament\Actions\Action;
use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index'); // Redirect to the list page
    }

    protected function getHeaderActions(): array
    {
      
        return [
            Action::make('Print Before')
            ->label('Print Before')
            ->icon('heroicon-o-printer')
            ->url(fn () => filled($this->record?->service_request_id)
                ? route('service-requests.show', $this->record->service_request_id)
                : null
            )
            ->visible(fn () => filled($this->record?->service_request_id))
            ->openUrlInNewTab(),

            Action::make('Print After')
            ->label('Print After')
            ->icon('heroicon-o-printer')
            ->url(fn () => filled($this->record?->service_request_id)
                ? route('service-requests.after', $this->record->service_request_id)
                : null
            )
            ->visible(fn () => filled($this->record?->service_request_id))
            ->openUrlInNewTab(),

        ];
    }


}

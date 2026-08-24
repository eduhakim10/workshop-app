<?php

namespace App\Filament\Resources\ServiceResource\Pages;
use Filament\Actions\Action;
use App\Filament\Resources\ServiceResource;
use App\Filament\Resources\ServiceResource\Pages\CreateService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index'); // Redirect to the list page
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return CreateService::applyPricingTotals($data);
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

            Action::make('downloadPo')
                ->label('Download PO Customer')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => filled($this->record->po_file))
                ->action(function () {
                    $path = $this->record->po_file;
                    if (! $path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                        \Filament\Notifications\Notification::make()
                            ->title('File PO tidak ditemukan di storage.')
                            ->danger()
                            ->send();

                        return null;
                    }

                    $filename = 'PO-' . ($this->record->po_number ?: $this->record->id) . '.pdf';

                    return \Illuminate\Support\Facades\Storage::disk('public')->download($path, $filename);
                }),
        ];
    }


}

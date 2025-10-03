<?php

namespace App\Filament\Resources\QuotationsResource\Pages;

use App\Filament\Resources\QuotationsResource;
use App\Filament\Resources\ServiceResource;

use Filament\Pages\Actions\Action;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditQuotations extends EditRecord
{
    protected static string $resource = QuotationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
                        Action::make('Approve to Service')
                ->label('Move to Service')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function () {
                    $quotation = $this->record;
                    $quotation->stage = 2;
                    $quotation->updated_at_offer = now();
                    $quotation->updated_at = now();

                    $itemsOffer = $quotation->items_offer;

                    // Kalau masih string JSON → decode
                    if (is_string($itemsOffer)) {
                        $itemsOffer = json_decode($itemsOffer, true);
                    }
                    
                    $finalItems = [];
                    
                    foreach ($itemsOffer as $group) {
                        if (isset($group['items']) && is_array($group['items'])) {
                            foreach ($group['items'] as $item) {
                                $finalItems[] = $item;
                            }
                        } else {
                            $finalItems[] = $group;
                        }
                    }
                    
                    // jangan encode, langsung assign array
                    $quotation->items = $finalItems;
                    

                    $this->record->save();

                 //   $this->notify('success', 'Quotation approved as service.');
                 Notification::make()
                    ->title('Quotation updated successfully')
                    ->success()
                    ->send();
                    $this->redirect(ServiceResource::getUrl());
                })
                ->visible(fn () => $this->record->stage == 1), 


                 Action::make('Print Overview')
                ->label('Print Overview')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('quotation.print.overview', $this->record))
                ->openUrlInNewTab(),

            Action::make('Print Detail')
                ->label('Print Detail')
                ->icon('heroicon-o-document-text')
                ->url(fn () => route('quotation.print.detail', $this->record))
                ->openUrlInNewTab(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
        {
            // kalau action nya edit, set updated_at_offer
            if ($this->record) {
                $data['updated_at_offer'] = now();
            }

            return $data;
        }
        protected function getActions(): array
    {
        return [
            Action::make('Approve to Service')
                ->label('Approve to Service')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->stage = 2;
                    $this->record->save();

                    $this->notify('success', 'Quotation approved as service.');
                    $this->redirect(ServiceResource::getUrl()); // Arahkan ke service list
                }),
                Action::make('Print Overview')
            ->label('Print Overview')
            ->icon('heroicon-o-printer')
            ->url(fn () => route('quotation.print.overview', $this->record)) // route bisa lo atur
            ->openUrlInNewTab(),

        Action::make('Print Detail')
            ->label('Print Detail')
            ->icon('heroicon-o-document-text')
            ->url(fn () => route('quotation.print.detail', $this->record))
            ->openUrlInNewTab(),

                ];
    }

}

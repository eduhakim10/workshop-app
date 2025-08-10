<?php

namespace App\Filament\Resources\QuotationsResource\Pages;

use App\Filament\Resources\QuotationsResource;
use Filament\Pages\Actions\Action;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotations extends EditRecord
{
    protected static string $resource = QuotationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
                        Action::make('Approve to Service')
                ->label('Approve to Service')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function () {
                    $quotation = $this->record;
                    $quotation->stage = 2;
                    // $this->record->stage = 2;

                     $quotation->items = $quotation->items_offer;
                    $this->record->save();

                 //   $this->notify('success', 'Quotation approved as service.');
                    $this->notify('success', 'Quotation approved to service.');
                    $this->redirect(ServiceResource::getUrl());
                })
                ->visible(fn () => $this->record->stage == 1), // Optional: hanya tampil jika belum di-approve


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

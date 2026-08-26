<?php

namespace App\Filament\Resources\QuotationsResource\Pages;

use App\Filament\Resources\QuotationsResource;
use App\Filament\Resources\QuotationsResource\Pages\CreateQuotations;
use App\Filament\Resources\ServiceResource;

use Filament\Pages\Actions\Action;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditQuotations extends EditRecord
{
    protected static string $resource = QuotationsResource::class;

    public function mount($record = null): void
    {
        parent::mount($record);

        if ($message = session('info')) {
            Notification::make()
                ->title($message)
                ->warning()
                ->send();
        }
    }

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
                    $quotation->created_at = now();

                    // Assign to / Document Position biasanya sudah dari penawaran
                    // (Prepared by / Location) — pastikan terisi saat pindah ke Service.
                    $quotation->applyQuotationDefaults();

                    $itemsOffer = $quotation->items_offer;

                    // Kalau masih string JSON → decode
                    if (is_string($itemsOffer)) {
                        $itemsOffer = json_decode($itemsOffer, true);
                    }

                    $finalItems = [];

                    foreach (($itemsOffer ?? []) as $group) {
                        if (isset($group['items']) && is_array($group['items'])) {
                            foreach ($group['items'] as $item) {
                                $finalItems[] = $item;
                            }
                        } else {
                            $finalItems[] = $group;
                        }
                    }

                    // Reset items first to ensure DB recognizes the change when overwritten
                    $quotation->items = null;
                    $quotation->save();

                    // Timpa dengan items final
                    $quotation->items = $finalItems;

                    // Status Portal harus jadi Antrian saat Move to Service
                    // (dari Kendaraan Diterima / kosong → Antrian). Set di akhir agar tidak tertimpa.
                    $antrianId = \App\Models\PortalServiceStatus::idByCode('antrian');
                    if (! $antrianId) {
                        Notification::make()
                            ->title('Status portal "Antrian" belum ada di master. Move to Service dibatalkan sebagian — cek Master Data → Status Service Portal.')
                            ->danger()
                            ->send();
                    } else {
                        $quotation->portal_service_status_id = $antrianId;
                    }

                    // Status Service (form) otomatis In Progress
                    $quotation->status = 'In Progress';

                    $quotation->save();

                    // Pastikan kolom status tersimpan (hindari race/form state menimpa)
                    $forceUpdate = [
                        'stage' => 2,
                        'status' => 'In Progress',
                    ];
                    if ($antrianId) {
                        $forceUpdate['portal_service_status_id'] = $antrianId;
                    }
                    \App\Models\Service::whereKey($quotation->id)->update($forceUpdate);

                    Notification::make()
                        ->title('Quotation updated successfully')
                        ->success()
                        ->send();
                    $this->redirect(ServiceResource::getUrl());
                })
                ->visible(fn () => (int) $this->record->stage === 1), 


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

            Actions\Action::make('downloadPo')
                ->label('Download PO Customer')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => filled($this->record->po_file))
                ->action(function () {
                    $path = $this->record->po_file;
                    if (! $path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                        Notification::make()
                            ->title('File PO tidak ditemukan di storage.')
                            ->danger()
                            ->send();

                        return null;
                    }

                    $filename = 'PO-' . ($this->record->po_number ?: $this->record->offer_number ?: $this->record->id) . '.pdf';

                    return \Illuminate\Support\Facades\Storage::disk('public')->download($path, $filename);
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
        {
            if ($this->record) {
                $data['updated_at_offer'] = now();
            }

            $data = CreateQuotations::applyPricingTotals($data);

            return \App\Models\Service::applyQuotationDefaultsToFormData($data);
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
                    $this->record->status = 'In Progress';
                    $this->record->applyQuotationDefaults();

                    $antrianId = \App\Models\PortalServiceStatus::idByCode('antrian');
                    if ($antrianId) {
                        $this->record->portal_service_status_id = $antrianId;
                    }
                    $this->record->save();

                    $forceUpdate = [
                        'stage' => 2,
                        'status' => 'In Progress',
                    ];
                    if ($antrianId) {
                        $forceUpdate['portal_service_status_id'] = $antrianId;
                    }
                    \App\Models\Service::whereKey($this->record->id)->update($forceUpdate);

                    $this->notify('success', 'Quotation approved as service.');
                    $this->redirect(ServiceResource::getUrl());
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

<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use App\Helpers\QuotationPricing;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::applyPricingTotals($data);
    }

    /**
     * Recompute & persist pricing totals saat save.
     * Mirror dari CreateQuotations::applyPricingTotals tapi memakai
     * struktur flat `items` (bukan groups).
     */
    public static function applyPricingTotals(array $data): array
    {
        $items = $data['items'] ?? [];

        $totals = QuotationPricing::calcFromFlat(
            $items,
            $data['ppn_type'] ?? QuotationPricing::PPN_NONE,
            $data['ppn_percent'] ?? 0
        );

        $data['subtotal_amount'] = $totals['subtotal'];
        $data['discount_total']  = $totals['discount'];
        $data['total_price']     = $totals['total'];

        if (empty($data['amount_offer']) || (float) $data['amount_offer'] == 0.0) {
            $data['amount_offer'] = $totals['total'];
        }

        return $data;
    }
}

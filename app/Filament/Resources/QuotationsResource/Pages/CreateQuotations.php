<?php

namespace App\Filament\Resources\QuotationsResource\Pages;

use App\Filament\Resources\QuotationsResource;
use App\Helpers\QuotationPricing;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotations extends CreateRecord
{
    protected static string $resource = QuotationsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['stage'] = 1;
        $data['created_at_offer'] = now();

        return self::applyPricingTotals($data);
    }

    protected function getRedirectUrl(): string
    {
        return QuotationsResource::getUrl();
    }

    /**
     * Pastikan field total/amount/group price tersimpan konsisten,
     * sebagai safety net dari reactive form (terutama untuk nested Repeater).
     */
    public static function applyPricingTotals(array $data): array
    {
        $groups = $data['items_offer'] ?? [];

        // Update Group Price tiap group dari sum line subtotal
        foreach ($groups as $idx => $group) {
            $totals = QuotationPricing::calcGroup($group);
            $groups[$idx]['price'] = $totals['subtotal'];
        }
        $data['items_offer'] = $groups;

        $totals = QuotationPricing::calcFromGroups(
            $groups,
            $data['ppn_type'] ?? QuotationPricing::PPN_NONE,
            $data['ppn_percent'] ?? 0
        );

        $data['subtotal_amount'] = $totals['subtotal'];
        $data['discount_total']  = $totals['discount'];
        $data['total_price']     = $totals['total'];

        // Auto-fill amount_offer jika kosong / mengikuti total
        if (empty($data['amount_offer']) || (float) $data['amount_offer'] == 0.0) {
            $data['amount_offer'] = $totals['total'];
        }

        return $data;
    }
}

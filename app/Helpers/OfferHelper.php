<?php

namespace App\Helpers;

use App\Models\Service;

class OfferHelper
{
    public static function generateOfferNumber(): string
    {
        $month = now()->format('m');
        $year = now()->format('y');

        $lastNumber = Service::whereMonth('created_at', $month)
            ->whereYear('created_at', '20' . $year)
            ->orderByDesc('id')
            ->value('offer_number');

        if ($lastNumber && preg_match('/^(\d{5})/', $lastNumber, $matches)) {
            $lastSeq = (int) $matches[1];
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        $formattedSeq = str_pad($nextSeq, 5, '0', STR_PAD_LEFT);

        return "{$formattedSeq}/MTI-MRK-QR/{$month}/{$year}";
    }
    public static function generateUniqueOfferNumber(): string
    {
        do {
            $offerNumber = self::generateOfferNumber();
        } while (Service::where('offer_number', $offerNumber)->exists());

        return $offerNumber;
    }

}

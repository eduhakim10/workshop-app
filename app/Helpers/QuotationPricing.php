<?php

namespace App\Helpers;

/**
 * Sentralisasi perhitungan harga untuk Quotation & Service.
 *
 * Kanon yang dipakai konsisten di form Filament, print blade,
 * dan API agar perhitungan tidak menyimpang antar tempat.
 *
 * Struktur input:
 *   - groups: untuk Quotation (items_offer berbentuk groups -> items)
 *   - flat:   untuk Service (items berbentuk flat list)
 *
 * Setiap line item mendukung field opsional:
 *   - sales_price (float)
 *   - quantity    (float)
 *   - discount_percent (float, %)
 *
 * Output: array gross, discount, subtotal (DPP), ppn, total.
 */
class QuotationPricing
{
    public const PPN_NONE        = 'tidak_ada';
    public const PPN_NOT_LEVIED  = 'tidak_dipungut';
    public const PPN_INCLUSIVE   = 'inklusif';
    public const PPN_EXCLUSIVE   = 'eksklusif';

    public static function ppnTypeOptions(): array
    {
        return [
            self::PPN_NONE       => 'Tidak ada PPN',
            self::PPN_NOT_LEVIED => 'PPN Tidak Dipungut Pajak',
            self::PPN_INCLUSIVE  => 'PPN Inklusif',
            self::PPN_EXCLUSIVE  => 'PPN Eksklusif (+%)',
        ];
    }

    /**
     * Hitung total satu line item.
     *
     * @return array{gross:float, discount:float, subtotal:float}
     */
    public static function calcLine(array $item): array
    {
        $price = self::toFloat($item['sales_price'] ?? 0);
        $qty   = self::toFloat($item['quantity'] ?? 0);
        $disc  = self::toFloat($item['discount_percent'] ?? 0);

        $gross    = $price * $qty;
        $discount = $gross * ($disc / 100);
        $subtotal = $gross - $discount;

        return [
            'gross'    => round($gross, 2),
            'discount' => round($discount, 2),
            'subtotal' => round($subtotal, 2),
        ];
    }

    /**
     * Hitung total satu group (kumpulan items).
     *
     * @return array{gross:float, discount:float, subtotal:float}
     */
    public static function calcGroup(array $group): array
    {
        $gross = 0.0;
        $discount = 0.0;

        foreach (($group['items'] ?? []) as $item) {
            $line = self::calcLine($item);
            $gross    += $line['gross'];
            $discount += $line['discount'];
        }

        return [
            'gross'    => round($gross, 2),
            'discount' => round($discount, 2),
            'subtotal' => round($gross - $discount, 2),
        ];
    }

    /**
     * Kalkulasi keseluruhan dari struktur groups (items_offer Quotation).
     */
    public static function calcFromGroups(array $groups, ?string $ppnType, $ppnPercent): array
    {
        $gross = 0.0;
        $discount = 0.0;

        foreach ($groups as $group) {
            $totals = self::calcGroup($group);
            $gross    += $totals['gross'];
            $discount += $totals['discount'];
        }

        return self::applyPpn($gross, $discount, $ppnType, $ppnPercent);
    }

    /**
     * Kalkulasi keseluruhan dari list flat (items Service).
     */
    public static function calcFromFlat(array $items, ?string $ppnType, $ppnPercent): array
    {
        $gross = 0.0;
        $discount = 0.0;

        foreach ($items as $item) {
            $line = self::calcLine($item);
            $gross    += $line['gross'];
            $discount += $line['discount'];
        }

        return self::applyPpn($gross, $discount, $ppnType, $ppnPercent);
    }

    /**
     * Terapkan PPN ke subtotal (gross - discount).
     *
     * Logika untuk setiap jenis:
     *  - tidak_ada       : tidak ada PPN, total = subtotal
     *  - tidak_dipungut  : PPN tetap ditampilkan namun tidak ditambahkan
     *                      ke total (Pajak Tidak Dipungut)
     *  - inklusif        : harga sudah termasuk PPN; subtotal/DPP dihitung
     *                      mundur dari (gross - discount) / (1 + r)
     *  - eksklusif       : PPN ditambahkan di atas DPP
     *
     * @return array{gross:float, discount:float, subtotal:float, ppn:float, ppn_percent:float, ppn_type:string, total:float}
     */
    public static function applyPpn(float $gross, float $discount, ?string $ppnType, $ppnPercent): array
    {
        $ppnType = $ppnType ?: self::PPN_NONE;
        $rate    = self::toFloat($ppnPercent) / 100;
        $base    = max(0.0, $gross - $discount);

        switch ($ppnType) {
            case self::PPN_INCLUSIVE:
                $dpp   = $rate > 0 ? $base / (1 + $rate) : $base;
                $ppn   = $base - $dpp;
                $total = $base; // total tetap = base (sudah termasuk PPN)
                break;

            case self::PPN_EXCLUSIVE:
                $dpp   = $base;
                $ppn   = $base * $rate;
                $total = $base + $ppn;
                break;

            case self::PPN_NOT_LEVIED:
                $dpp   = $base;
                $ppn   = $base * $rate; // ditampilkan, tapi tidak ditambahkan
                $total = $base;
                break;

            case self::PPN_NONE:
            default:
                $dpp   = $base;
                $ppn   = 0.0;
                $total = $base;
                break;
        }

        return [
            'gross'       => round($gross, 2),
            'discount'    => round($discount, 2),
            'subtotal'    => round($dpp, 2),
            'ppn'         => round($ppn, 2),
            'ppn_percent' => self::toFloat($ppnPercent),
            'ppn_type'    => $ppnType,
            'total'       => round($total, 2),
        ];
    }

    public static function ppnTypeLabel(?string $ppnType): string
    {
        return self::ppnTypeOptions()[$ppnType ?? self::PPN_NONE] ?? 'Tidak ada PPN';
    }

    private static function toFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Handle format ID: "1.000,50" -> 1000.50
        $clean = str_replace('.', '', (string) $value);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}

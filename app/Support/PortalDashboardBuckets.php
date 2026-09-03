<?php

namespace App\Support;

use App\Models\PortalServiceStatus;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared dashboard / quotation filters for the customer portal.
 * Keep DashboardController stats and Quotation list tabs in sync.
 */
class PortalDashboardBuckets
{
    public const SEDANG_MENUNGGU = 'sedang_menunggu';

    public const SEDANG_DIPERBAIKI = 'sedang_diperbaiki';

    public const SUDAH_DIPERBAIKI = 'sudah_diperbaiki';

    public const BELUM_ADA_PO = 'belum_ada_po';

    /** @return list<string> */
    public static function keys(): array
    {
        return [
            self::SEDANG_MENUNGGU,
            self::SEDANG_DIPERBAIKI,
            self::SUDAH_DIPERBAIKI,
            self::BELUM_ADA_PO,
        ];
    }

    public static function applySedangMenunggu(Builder $query): Builder
    {
        return $query
            ->where('stage', 1)
            ->whereIn('quotation_status', ['Draft', 'Sent', 'Revised']);
    }

    /**
     * Sedang diperbaiki = status portal Dikerjakan (bukan Antrian).
     * Antrian = sudah masuk workshop tapi belum dikerjakan.
     */
    public static function applySedangDiperbaiki(Builder $query): Builder
    {
        $dikerjakanId = PortalServiceStatus::idByCode('dikerjakan');

        $query
            ->where('stage', 2)
            ->whereNotNull('sr_number')
            ->where('sr_number', '!=', '');

        if ($dikerjakanId) {
            return $query->where('portal_service_status_id', $dikerjakanId);
        }

        return $query->whereHas(
            'portalServiceStatus',
            fn (Builder $q) => $q->where('code', 'dikerjakan')
        );
    }

    public static function applySudahDiperbaiki(Builder $query): Builder
    {
        return $query
            ->where('stage', 2)
            ->whereNotNull('sr_number')
            ->where('sr_number', '!=', '')
            ->whereHas('afterPhotos');
    }

    public static function applyBelumAdaPo(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('po_file')->orWhere('po_file', '');
        });
    }

    /**
     * Workshop belum selesai (Antrian / Dikerjakan / belum ada foto after).
     * Dipakai agar Antrian tetap muncul di tab Semua.
     */
    public static function applyWorkshopBelumSelesai(Builder $query): Builder
    {
        return $query
            ->where('stage', 2)
            ->whereNotNull('sr_number')
            ->where('sr_number', '!=', '')
            ->whereDoesntHave('afterPhotos');
    }

    /** Union of all dashboard buckets (for "Semua" list). */
    public static function applyAny(Builder $query): Builder
    {
        return $query->where(function (Builder $outer) {
            $outer
                ->where(fn (Builder $q) => self::applySedangMenunggu($q))
                ->orWhere(fn (Builder $q) => self::applyWorkshopBelumSelesai($q))
                ->orWhere(fn (Builder $q) => self::applySudahDiperbaiki($q))
                ->orWhere(fn (Builder $q) => self::applyBelumAdaPo($q));
        });
    }

    public static function applyBucket(Builder $query, string $bucket): Builder
    {
        return match ($bucket) {
            self::SEDANG_MENUNGGU => self::applySedangMenunggu($query),
            self::SEDANG_DIPERBAIKI => self::applySedangDiperbaiki($query),
            self::SUDAH_DIPERBAIKI => self::applySudahDiperbaiki($query),
            self::BELUM_ADA_PO => self::applyBelumAdaPo($query),
            default => $query,
        };
    }

    /**
     * @return array{sedang_menunggu: bool, sedang_diperbaiki: bool, sudah_diperbaiki: bool, belum_ada_po: bool}
     */
    public static function flags(Service $s): array
    {
        $hasSr = filled($s->sr_number);
        $hasAfter = $s->relationLoaded('afterPhotos')
            ? $s->afterPhotos->isNotEmpty()
            : $s->afterPhotos()->exists();

        $statusCode = self::portalStatusCode($s);

        return [
            self::SEDANG_MENUNGGU => (int) $s->stage === 1
                && in_array((string) $s->quotation_status, ['Draft', 'Sent', 'Revised'], true),
            self::SEDANG_DIPERBAIKI => (int) $s->stage === 2 && $hasSr && $statusCode === 'dikerjakan',
            self::SUDAH_DIPERBAIKI => (int) $s->stage === 2 && $hasSr && $hasAfter,
            self::BELUM_ADA_PO => ! filled($s->po_file),
        ];
    }

    /**
     * @return array{sedang_menunggu: int, sedang_diperbaiki: int, sudah_diperbaiki: int, belum_ada_po: int}
     */
    public static function counts(int $customerId, string $from, string $to): array
    {
        $base = fn () => Service::where('customer_id', $customerId)
            ->inPortalPeriod($from, $to);

        return [
            self::SEDANG_MENUNGGU => self::applySedangMenunggu($base())->count(),
            self::SEDANG_DIPERBAIKI => self::applySedangDiperbaiki($base())->count(),
            self::SUDAH_DIPERBAIKI => self::applySudahDiperbaiki($base())->count(),
            self::BELUM_ADA_PO => self::applyBelumAdaPo($base())->count(),
        ];
    }

    private static function portalStatusCode(Service $s): string
    {
        if ($s->relationLoaded('portalServiceStatus') && $s->portalServiceStatus) {
            return (string) $s->portalServiceStatus->code;
        }

        return (string) (ServicePresenter::portalStatusBadge($s)['code'] ?? '');
    }
}
